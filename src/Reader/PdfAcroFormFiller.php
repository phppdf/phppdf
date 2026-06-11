<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfString;
use RuntimeException;

/**
 * Fills AcroForm fields by appending an incremental update to the original
 * PDF bytes. The original file content is not modified; new object definitions
 * are appended along with an updated xref section and trailer.
 *
 * Only type-'n' (normal, file-offset) objects can be updated. Fields stored
 * inside object streams (type-'s') are silently skipped because rewriting an
 * ObjStm would require a full re-serialization.
 *
 * Usage:
 *   $filler = new PdfAcroFormFiller($document, $originalBytes);
 *   $filler->setText($field, 'John Doe');
 *   $filler->setChecked($checkboxField, true);
 *   file_put_contents('filled.pdf', $filler->getBytes());
 */
final class PdfAcroFormFiller
{
    /** @var array<int, \PhpPdf\Object\PdfDictionary> objectNumber → modified dict */
    private array $modifications = [];

    private readonly ?PdfEncryptionContext $encryptionContext;

    public function __construct(private readonly PdfReadDocument $document, private readonly string $originalBytes,)
    {
        $this->encryptionContext = $document->getDecryptionContext();
    }

    /**
     * Sets a text field value.
     */
    public function setText(PdfFormField $field, string $value): void
    {
        if ($field->readOnly) {
            return;
        }

        $dict = $this->getOrCloneDict($field);

        if ($dict === null) {
            return;
        }

        $dict->set('V', new PdfString($value));
        // Remove pre-rendered appearance so viewers regenerate it.
        $dict->set('AP', new PdfDictionary());
    }

    /**
     * Checks or unchecks a checkbox field.
     *
     * The on-state name may differ from /Yes in some PDFs; this implementation
     * uses the standard /Yes and /Off values.
     */
    public function setChecked(PdfFormField $field, bool $checked): void
    {
        if ($field->readOnly || $field->type !== PdfFormFieldType::Button) {
            return;
        }

        $dict = $this->getOrCloneDict($field);

        if ($dict === null) {
            return;
        }

        $stateName = $checked
            ? 'Yes'
            : 'Off';
        $dict->set('V', new PdfName($stateName));
        $dict->set('AS', new PdfName($stateName));
    }

    /**
     * Selects a choice (combo box or list box) field value.
     */
    public function setChoice(PdfFormField $field, string $value): void
    {
        if ($field->readOnly || $field->type !== PdfFormFieldType::Choice) {
            return;
        }

        $dict = $this->getOrCloneDict($field);

        if ($dict === null) {
            return;
        }

        $dict->set('V', new PdfString($value));
        $dict->set('AP', new PdfDictionary());
    }

    /**
     * Serializes the original PDF bytes followed by the incremental update and
     * returns the complete PDF as a string.
     */
    public function getBytes(): string
    {
        if (empty($this->modifications)) {
            return $this->originalBytes;
        }

        $update = '';
        $newOffsets = []; // objectNumber → offset within the full output

        $baseOffset = strlen($this->originalBytes);

        // Ensure the AcroForm dict has NeedAppearances = true so viewers
        // render the field values even without pre-built appearance streams.
        $acroFormObjNum = $this->getAcroFormObjectNumber();

        // Serialize each modified object.
        foreach ($this->modifications as $objNum => $dict) {
            $newOffsets[$objNum] = $baseOffset + strlen($update);
            $gen = $this->getGeneration($objNum);
            $update .= $this->serializeObject($objNum, $gen, $dict);
        }

        // If AcroForm itself was not among the modified objects, append a patch
        // that sets NeedAppearances=true so viewers honour our /V updates.
        if ($acroFormObjNum > 0 && !isset($this->modifications[$acroFormObjNum])) {
            $acroFormDict = $this->loadDictForObject($acroFormObjNum);

            if ($acroFormDict !== null) {
                $acroFormDict->set('NeedAppearances', new PdfBoolean(true));
                $newOffsets[$acroFormObjNum] = $baseOffset + strlen($update);
                $gen = $this->getGeneration($acroFormObjNum);
                $update .= $this->serializeObject($acroFormObjNum, $gen, $acroFormDict);
            }
        }

        // Build the cross-reference section.
        $xrefOffset = $baseOffset + strlen($update);
        $update .= $this->buildXRef($newOffsets);

        // Build the trailer.
        $trailer = $this->document->getTrailer();
        $size = (max([0, ...array_keys($this->document->getXref())])) + 1;
        $size = max($size, max([0, ...array_keys($newOffsets)]) + 1);

        $update .= $this->buildTrailer($trailer, $size, $this->document->getStartXRefOffset(), $xrefOffset);

        return $this->originalBytes . $update;
    }

    /**
     * Writes the filled PDF to a file.
     *
     * @throws \RuntimeException on write failure.
     */
    public function save(string $filePath): void
    {
        $bytes = $this->getBytes();

        if (file_put_contents($filePath, $bytes) === false) {
            throw new RuntimeException("Failed to write PDF to: {$filePath}");
        }
    }

    // -------------------------------------------------------------------------

    private function getOrCloneDict(PdfFormField $field): ?PdfDictionary
    {
        $objNum = $field->objectNumber;

        if ($objNum === 0) {
            return null;
        }

        // Only type-'n' objects can be placed at a file offset in the xref.
        $xref = $this->document->getXref();

        if (!isset($xref[$objNum]) || $xref[$objNum]['type'] !== 'n') {
            return null;
        }

        if (isset($this->modifications[$objNum])) {
            return $this->modifications[$objNum];
        }

        // Load the existing dict and clone it so we don't mutate the document cache.
        $obj = $this->document->getObject(
            new PdfIndirectReference($objNum, $field->generationNumber),
        );

        if (!$obj instanceof PdfDictionary) {
            return null;
        }

        $clone = new PdfDictionary($obj->getEntries());
        $this->modifications[$objNum] = $clone;

        return $clone;
    }

    private function loadDictForObject(int $objNum): ?PdfDictionary
    {
        $xref = $this->document->getXref();

        if (!isset($xref[$objNum]) || $xref[$objNum]['type'] !== 'n') {
            return null;
        }

        $gen = $xref[$objNum]['generation'] ?? 0;
        $obj = $this->document->getObject(new PdfIndirectReference($objNum, $gen));

        if (!$obj instanceof PdfDictionary) {
            return null;
        }

        return new PdfDictionary($obj->getEntries());
    }

    private function getGeneration(int $objNum): int
    {
        $xref = $this->document->getXref();

        return $xref[$objNum]['generation'] ?? 0;
    }

    private function getAcroFormObjectNumber(): int
    {
        $catalog = $this->document->getCatalog();
        $acroRef = $catalog->get('AcroForm');

        if ($acroRef instanceof PdfIndirectReference) {
            return $acroRef->getObjectNumber();
        }

        return 0;
    }

    // -------------------------------------------------------------------------
    // Inline serializer
    // -------------------------------------------------------------------------

    private function serializeObject(int $objNum, int $gen, PdfDictionary $dict): string
    {
        $body = $this->serializeDict($dict, $objNum, $gen);

        return "{$objNum} {$gen} obj\n{$body}\nendobj\n";
    }

    private function serializeDict(PdfDictionary $dict, int $objNum, int $gen): string
    {
        $out = '<< ';

        foreach ($dict->getEntries() as $key => $value) {
            $out .= "/{$key} " . $this->serializeValue($value, $objNum, $gen) . ' ';
        }

        $out .= '>>';

        return $out;
    }

    private function serializeValue(PdfObject $value, int $objNum, int $gen): string
    {
        return match (true) {
            $value instanceof PdfName => '/' . $this->escapeName($value->getValue()),
            $value instanceof PdfString => $this->serializeString($value->getValue(), $objNum, $gen),
            $value instanceof PdfInteger => (string) $value->getValue(),
            $value instanceof PdfBoolean => $value->getValue() ? 'true' : 'false',
            $value instanceof PdfIndirectReference => "{$value->getObjectNumber()} {$value->getGenerationNumber()} R",
            $value instanceof PdfArray => $this->serializeArray($value, $objNum, $gen),
            $value instanceof PdfDictionary => $this->serializeDict($value, $objNum, $gen),
            default => 'null',
        };
    }

    private function serializeString(string $plain, int $objNum, int $gen): string
    {
        if ($this->encryptionContext !== null && $this->encryptionContext->shouldEncryptObject($objNum)) {
            $cipher = $this->encryptionContext->encrypt($plain, $objNum, $gen);

            return '<' . bin2hex($cipher) . '>';
        }

        return '(' . $this->escapeLiteralString($plain) . ')';
    }

    private function serializeArray(PdfArray $array, int $objNum, int $gen): string
    {
        $parts = [];

        foreach ($array->getItems() as $item) {
            $parts[] = $this->serializeValue($item, $objNum, $gen);
        }

        return '[ ' . implode(' ', $parts) . ' ]';
    }

    private function escapeLiteralString(string $s): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '\\r', '\\n'], $s);
    }

    private function escapeName(string $name): string
    {
        // Escape non-regular characters with #xx notation (PDF spec §7.3.5).
        return preg_replace_callback(
            '/[^\x21-\x7E]|[#()<>\[\]{}\/%]/',
            static fn ($m) => '#' . strtoupper(bin2hex($m[0])),
            $name,
        ) ?? $name;
    }

    // -------------------------------------------------------------------------
    // xref + trailer builder
    // -------------------------------------------------------------------------

    /** @param array<int, int> $offsets objectNumber → byte offset in full output */
    private function buildXRef(array $offsets): string
    {
        ksort($offsets);
        $out = "xref\n";

        // Group consecutive object numbers into subsections.
        $groups = [];
        $prev = -2;
        $group = [];

        foreach ($offsets as $num => $offset) {
            if ($num !== $prev + 1) {
                if (!empty($group)) {
                    $groups[] = $group;
                }

                $group = [];
            }

            $group[$num] = $offset;
            $prev = $num;
        }

        if (!empty($group)) {
            $groups[] = $group;
        }

        foreach ($groups as $group) {
            $firstNum = array_key_first($group);
            $count = count($group);
            $out .= "{$firstNum} {$count}\n";

            foreach ($group as $num => $offset) {
                $gen = $this->getGeneration($num);
                $out .= sprintf("%010d %05d n \n", $offset, $gen);
            }
        }

        return $out;
    }

    private function buildTrailer(PdfDictionary $original, int $size, int $prevXref, int $xrefOffset): string
    {
        $entries = [];

        // /Size must be at least one more than the highest object number in the xref.
        $entries['Size'] = new PdfInteger($size);
        $entries['Prev'] = new PdfInteger($prevXref);

        // Carry forward /Root and /Info references unchanged.
        $root = $original->get('Root');

        if ($root !== null) {
            $entries['Root'] = $root;
        }

        $info = $original->get('Info');

        if ($info !== null) {
            $entries['Info'] = $info;
        }

        $id = $original->get('ID');

        if ($id !== null) {
            $entries['ID'] = $id;
        }

        $trailerDict = '<< ';

        foreach ($entries as $key => $value) {
            $trailerDict .= "/{$key} " . $this->serializeValue($value, 0, 0) . ' ';
        }

        $trailerDict .= '>>';

        return "trailer\n{$trailerDict}\nstartxref\n{$xrefOffset}\n%%EOF\n";
    }
}
