<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfString;

/**
 * Walks the AcroForm field tree and returns a flat list of leaf fields.
 *
 * Fields are inherited from parent nodes per the PDF spec (§12.7.3.3):
 * /FT, /Ff, and /V may be inherited. /T partial names are concatenated
 * with '.' to form the full qualified name.
 */
final class PdfAcroFormReader
{
    /** @var list<\PhpPdf\Reader\PdfFormField> */
    private array $fields = [];

    public function __construct(private readonly PdfReadDocument $document,)
    {
    }

    /** @return list<\PhpPdf\Reader\PdfFormField> */
    public function getFields(): array
    {
        if (empty($this->fields)) {
            $this->collect();
        }

        return $this->fields;
    }

    /** @return array<string, \PhpPdf\Reader\PdfFormField> keyed by full qualified name */
    public function getFieldsByName(): array
    {
        $result = [];

        foreach ($this->getFields() as $field) {
            $result[$field->fullName] = $field;
        }

        return $result;
    }

    // -------------------------------------------------------------------------

    private function collect(): void
    {
        $catalog = $this->document->getCatalog();
        $acroFormRef = $catalog->get('AcroForm');

        if ($acroFormRef === null) {
            return;
        }

        $acroForm = $this->document->resolveObject($acroFormRef);

        if (!$acroForm instanceof PdfDictionary) {
            return;
        }

        $fieldsRef = $acroForm->get('Fields');

        if ($fieldsRef === null) {
            return;
        }

        $fieldsArray = $this->document->resolveObject($fieldsRef);

        if (!$fieldsArray instanceof PdfArray) {
            return;
        }

        foreach ($fieldsArray->getItems() as $item) {
            $objNum = $item instanceof PdfIndirectReference
                ? $item->getObjectNumber()
                : 0;
            $gen = $item instanceof PdfIndirectReference
                ? $item->getGenerationNumber()
                : 0;
            $dict = $this->document->resolveObject($item);

            if (!($dict instanceof PdfDictionary)) {
                continue;
            }

            $this->walkNode($dict, '', $objNum, $gen, null, null, null);
        }
    }

    private function walkNode(
        PdfDictionary $node,
        string $parentFullName,
        int $objectNumber,
        int $generationNumber,
        ?string $inheritedFt,
        int|null $inheritedFf,
        PdfObject|null $inheritedV,
    ): void {
        // /T is the partial name of this node.
        $tRaw = $node->get('T');
        $partialName = $tRaw instanceof PdfString
            ? $tRaw->getValue()
            : '';
        $fullName = $parentFullName === ''
            ? $partialName
            : "{$parentFullName}.{$partialName}";

        // Resolve /FT, /Ff, /V from this node or fall back to inherited values.
        $ftName = $this->getNameValue($node, 'FT') ?? $inheritedFt;
        $ff = $this->getIntValue($node, 'Ff') ?? $inheritedFf;
        $vObject = $node->get('V') ?? $inheritedV;

        // Check for /Kids — if present, this is a non-terminal node.
        $kidsRef = $node->get('Kids');

        if ($kidsRef !== null) {
            $kids = $this->document->resolveObject($kidsRef);

            if ($kids instanceof PdfArray) {
                foreach ($kids->getItems() as $kidRef) {
                    $kidObjNum = $kidRef instanceof PdfIndirectReference
                        ? $kidRef->getObjectNumber()
                        : 0;
                    $kidGen = $kidRef instanceof PdfIndirectReference
                        ? $kidRef->getGenerationNumber()
                        : 0;
                    $kidDict = $this->document->resolveObject($kidRef);

                    if (!($kidDict instanceof PdfDictionary)) {
                        continue;
                    }

                    $this->walkNode($kidDict, $fullName, $kidObjNum, $kidGen, $ftName, $ff, $vObject);
                }

                return;
            }
        }

        // Leaf field: must have a /FT (possibly inherited).
        if ($ftName === null) {
            return;
        }

        $type = PdfFormFieldType::fromPdfName($ftName);
        $readOnly = (($ff ?? 0) & 1) === 1;
        $multiLine = (($ff ?? 0) & 0x1000) !== 0; // bit 13 (0-indexed bit 12)

        $value = $this->extractValue($node, $type, $vObject);
        $options = $this->extractOptions($node);

        $this->fields[] = new PdfFormField(
            objectNumber: $objectNumber,
            generationNumber: $generationNumber,
            name: $partialName,
            fullName: $fullName,
            type: $type,
            value: $value,
            options: $options,
            readOnly: $readOnly,
            multiLine: $multiLine,
        );
    }

    private function extractValue(
        PdfDictionary $node,
        PdfFormFieldType $type,
        PdfObject|null $vObject,
    ): string|bool|null {
        if ($vObject === null) {
            return null;
        }

        $resolved = $this->document->resolveObject($vObject);

        if ($type === PdfFormFieldType::Button) {
            // Checkbox: /V is a name — /Yes or /Off (or custom on-state).
            if ($resolved instanceof PdfName) {
                return $resolved->getValue() !== 'Off';
            }

            return null;
        }

        if ($resolved instanceof PdfString) {
            return $resolved->getValue();
        }

        if ($resolved instanceof PdfName) {
            return $resolved->getValue();
        }

        return null;
    }

    /** @return list<string> */
    private function extractOptions(PdfDictionary $node): array
    {
        $optRef = $node->get('Opt');

        if ($optRef === null) {
            return [];
        }

        $optArray = $this->document->resolveObject($optRef);

        if (!$optArray instanceof PdfArray) {
            return [];
        }

        $options = [];

        foreach ($optArray->getItems() as $item) {
            $resolved = $this->document->resolveObject($item);

            if ($resolved instanceof PdfString) {
                $options[] = $resolved->getValue();
            } elseif ($resolved instanceof PdfArray) {
                // Each /Opt item may be a 2-element array [export, display]; use display.
                $sub = $resolved->getItems();
                $display = $sub[1] ?? $sub[0] ?? null;

                if ($display !== null) {
                    $d = $this->document->resolveObject($display);

                    if ($d instanceof PdfString) {
                        $options[] = $d->getValue();
                    }
                }
            }
        }

        return $options;
    }

    private function getNameValue(PdfDictionary $dict, string $key): ?string
    {
        $val = $dict->get($key);

        return $val instanceof PdfName
            ? $val->getValue()
            : null;
    }

    private function getIntValue(PdfDictionary $dict, string $key): ?int
    {
        $val = $dict->get($key);

        return $val instanceof PdfInteger
            ? $val->getValue()
            : null;
    }
}
