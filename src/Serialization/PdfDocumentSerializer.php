<?php

declare(strict_types=1);

namespace PhpPdf\Serialization;

use LogicException;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDate;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfHexString;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfRawObject;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Output\PdfOutput;

final class PdfDocumentSerializer
{
    private ?PdfEncryptionContext $encryptionContext = null;
    private bool $compressionEnabled = false;

    /** Object number of the indirect object currently being serialized. */
    private int $currentObjectNumber = 0;
    private int $currentGenerationNumber = 0;

    public function __construct(private readonly PdfOutput $output,)
    {
    }

    /**
     * Entry point for all PDF objects.
     */
    public function write(PdfObject $object): void
    {
        $object->serialize($this);
    }

    public function writeArray(PdfArray $array): void
    {
        $this->output->write('[');

        $first = true;

        foreach ($array->getItems() as $item) {
            if (!$first) {
                $this->output->write(' ');
            }

            $this->write($item);

            $first = false;
        }

        $this->output->write(']');
    }

    public function writeBoolean(PdfBoolean $boolean): void
    {
        $this->output->write($boolean->getValue() ? 'true' : 'false');
    }

    public function writeDate(PdfDate $date): void
    {
        $value = 'D:' . $date->getValue()->format('YmdHisO');

        if ($this->shouldEncrypt()) {
            $this->writeEncryptedString($value);

            return;
        }

        $this->output->write('(' . $value . ')');
    }

    public function writeDocument(PdfDocument $document): void
    {
        $this->encryptionContext = $document->getEncryptionContext();
        $this->compressionEnabled = $document->isCompressionEnabled();

        $this->writeHeader($document);

        $offsets = $this->writeIndirectObjects(
            $document->getObjects()->all(),
        );

        $xrefOffset = $this->output->position();

        $this->writeCrossReferenceTable($offsets);

        $this->writeTrailer($document, $offsets);

        $this->writeFooter($xrefOffset);
    }

    /**
     * Writes a single indirect object, tracking the current object context
     * so string and stream writers can derive per-object encryption keys.
     */
    public function writeIndirectObject(PdfIndirectObject $object): void
    {
        $this->currentObjectNumber = $object->getObjectNumber();
        $this->currentGenerationNumber = $object->getGenerationNumber();

        $this->output->write(sprintf(
            "%d %d obj\n",
            $object->getObjectNumber(),
            $object->getGenerationNumber(),
        ));

        $this->write($object->getObject());

        $this->output->write("\nendobj\n");
    }

    /**
     * Writes all indirect objects and returns their byte offsets.
     *
     * @param iterable<\PhpPdf\Object\PdfIndirectObject> $objects
     * @return array<int, int>
     */
    public function writeIndirectObjects(iterable $objects): array
    {
        $offsets = [];

        foreach ($objects as $object) {
            $offsets[$object->getObjectNumber()] = $this->output->position();
            $this->writeIndirectObject($object);
        }

        return $offsets;
    }

    public function writeDictionary(PdfDictionary $dictionary): void
    {
        $this->output->write("<<\n");

        foreach ($dictionary->getEntries() as $key => $value) {
            $this->output->write('/' . $key . ' ');
            $this->write($value);
            $this->output->write("\n");
        }

        $this->output->write('>>');
    }

    public function writeName(PdfName $name): void
    {
        $this->output->write('/' . $name->getValue());
    }

    public function writeHexString(PdfHexString $hexString): void
    {
        if ($this->shouldEncrypt()) {
            $this->writeEncryptedString($hexString->getBinary());

            return;
        }

        $this->output->write($hexString->toPdfString());
    }

    public function writeRawObject(PdfRawObject $object): void
    {
        $this->output->write($object->getValue());
    }

    public function writeString(PdfString $string): void
    {
        if ($this->shouldEncrypt()) {
            $this->writeEncryptedString($string->getValue());

            return;
        }

        $this->output->write('(' . $this->escapeString($string->getValue()) . ')');
    }

    public function writeInteger(PdfInteger $integer): void
    {
        $this->output->write((string) $integer->getValue());
    }

    public function writeReal(PdfReal $real): void
    {
        $this->output->write($real->toPdfString());
    }

    public function writeIndirectReference(PdfIndirectReference $reference): void
    {
        $this->output->write(sprintf(
            '%d %d R',
            $reference->getObjectNumber(),
            $reference->getGenerationNumber(),
        ));
    }

    public function writeStreamObject(PdfStream $stream): void
    {
        $content = $stream->getData()->serialize(new PdfStreamSerializer());

        // Compress before encrypting so the viewer decrypts then decompresses.
        // Skip streams that already declare a /Filter and XMP metadata streams.
        if ($this->compressionEnabled && !$this->streamHasFilter($stream) && !$this->isMetadataStream($stream)) {
            $compressed = gzcompress($content, 6);
            if ($compressed !== false) {
                $content = $compressed;
                $stream->getDictionary()->set('Filter', new PdfName('FlateDecode'));
            }
        }

        if ($this->shouldEncrypt() && $this->encryptionContext !== null) {
            $content = $this->encryptionContext->encrypt(
                $content,
                $this->currentObjectNumber,
                $this->currentGenerationNumber,
            );
        }

        $dictionary = $stream->getDictionary();
        $dictionary->set('Length', new PdfInteger(strlen($content)));

        $this->writeDictionary($dictionary);

        $this->output->write("\nstream\n");
        $this->output->write($content);
        $this->output->write("\nendstream");
    }

    private function writeHeader(PdfDocument $document): void
    {
        $this->output->write('%PDF-' . $document->getVersion()->value . "\n");

        // Binary marker required by PDF spec
        $this->output->write("%\xE2\xE3\xCF\xD3\n");
    }

    /** @param array<int, int> $offsets */
    private function writeCrossReferenceTable(array $offsets): void
    {
        $this->output->write("xref\n");

        $count = count($offsets) + 1;

        $this->output->write(sprintf("0 %d\n", $count));

        // Free object
        $this->output->write("0000000000 65535 f \n");

        foreach ($offsets as $offset) {
            $this->output->write(sprintf(
                "%010d 00000 n \n",
                $offset,
            ));
        }
    }

    /** @param array<int, int> $offsets */
    private function writeTrailer(PdfDocument $document, array $offsets): void
    {
        $this->output->write("trailer\n");
        $this->output->write("<<\n");

        $this->output->write(sprintf("/Size %d\n", count($offsets) + 1));

        $catalog = $document->getCatalog();
        $this->output->write(sprintf(
            "/Root %d %d R\n",
            $catalog->getObjectNumber(),
            $catalog->getGenerationNumber(),
        ));

        $info = $document->getInfo();

        if ($info !== null) {
            $this->output->write(sprintf(
                "/Info %d %d R\n",
                $info->getObjectNumber(),
                $info->getGenerationNumber(),
            ));
        }

        $documentId = $document->getDocumentId();

        if ($documentId !== null) {
            $hex = strtoupper(bin2hex($documentId));
            $this->output->write("/ID [<{$hex}> <{$hex}>]\n");
        }

        $encryptRef = $document->getEncryptDict();

        if ($encryptRef !== null) {
            $this->output->write(sprintf(
                "/Encrypt %d %d R\n",
                $encryptRef->getObjectNumber(),
                $encryptRef->getGenerationNumber(),
            ));
        }

        $this->output->write(">>\n");
    }

    private function writeFooter(int $xrefOffset): void
    {
        $this->output->write("startxref\n");
        $this->output->write((string) $xrefOffset);
        $this->output->write("\n%%EOF\n");
    }

    // -------------------------------------------------------------------------
    // Encryption helpers
    // -------------------------------------------------------------------------

    private function shouldEncrypt(): bool
    {
        return $this->encryptionContext !== null
            && $this->encryptionContext->shouldEncryptObject($this->currentObjectNumber);
    }

    /**
     * Encrypts $plaintext and writes it as a hex string (angle-bracket form).
     * Encrypted values are always binary, so literal-string form is unsafe.
     */
    private function writeEncryptedString(string $plaintext): void
    {
        if ($this->encryptionContext === null) {
            throw new LogicException('writeEncryptedString() called without an encryption context.');
        }

        $encrypted = $this->encryptionContext->encrypt(
            $plaintext,
            $this->currentObjectNumber,
            $this->currentGenerationNumber,
        );

        $this->output->write('<' . strtoupper(bin2hex($encrypted)) . '>');
    }

    // -------------------------------------------------------------------------

    private function streamHasFilter(PdfStream $stream): bool
    {
        return $stream->getDictionary()->has('Filter');
    }

    private function isMetadataStream(PdfStream $stream): bool
    {
        $type = $stream->getDictionary()->get('Type');

        return $type instanceof PdfName && $type->getValue() === 'Metadata';
    }

    private function escapeString(string $value): string
    {
        return str_replace(
            ['\\', '(', ')', "\r"],
            ['\\\\', '\\(', '\\)', '\\r'],
            $value,
        );
    }
}
