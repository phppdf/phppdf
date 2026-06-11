<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Serialization\PdfStreamSerializer;

/**
 * Represents a single page in a parsed PDF document.
 *
 * Provides access to the page dictionary, media box dimensions, decoded content
 * streams, and resource dictionary. Indirect references inside the page are
 * resolved through the owning PdfReadDocument on demand.
 */
final class PdfReadPage
{
    public function __construct(private readonly PdfDictionary $dictionary, private readonly PdfReadDocument $document,)
    {
    }

    public function getDictionary(): PdfDictionary
    {
        return $this->dictionary;
    }

    public function getDocument(): PdfReadDocument
    {
        return $this->document;
    }

    /**
     * Returns the /MediaBox as [x, y, width, height] in user units.
     * Falls back to [0, 0, 595, 842] (A4) if the entry is missing.
     *
     * @return array{float, float, float, float}
     */
    public function getMediaBox(): array
    {
        $mediaBox = $this->getDictValue('MediaBox');

        if ($mediaBox instanceof PdfIndirectReference) {
            $mediaBox = $this->document->resolveObject($mediaBox);
        }

        if (!$mediaBox instanceof PdfArray) {
            return [0.0, 0.0, 595.0, 842.0];
        }

        $values = array_map(
            static fn ($item) => match (true) {
                $item instanceof PdfInteger => (float) $item->getValue(),
                $item instanceof PdfReal => (float) $item->getValue(),
                default => 0.0,
            },
            $mediaBox->getItems(),
        );

        return [
            $values[0] ?? 0.0,
            $values[1] ?? 0.0,
            $values[2] ?? 595.0,
            $values[3] ?? 842.0,
        ];
    }

    /**
     * Returns the /Resources dictionary for this page.
     * Returns an empty dictionary if no resources are defined.
     */
    public function getResources(): PdfDictionary
    {
        $res = $this->getDictValue('Resources');

        if ($res === null) {
            return new PdfDictionary([]);
        }

        $resolved = $this->document->resolveObject($res);

        return $resolved instanceof PdfDictionary
            ? $resolved
            : new PdfDictionary([]);
    }

    /**
     * Returns the decoded content of all content streams for this page.
     * Each element is the raw (decoded) byte string of one stream.
     *
     * @return list<string>
     */
    public function getContentStreams(): array
    {
        $contents = $this->getDictValue('Contents');

        if ($contents === null) {
            return [];
        }

        $resolved = $this->document->resolveObject($contents);

        $refs = $resolved instanceof PdfArray
            ? $resolved->getItems()
            : [$resolved];

        $serializer = new PdfStreamSerializer();
        $streams = [];

        foreach ($refs as $ref) {
            $stream = $this->document->resolveObject($ref);

            if (!($stream instanceof PdfStream)) {
                continue;
            }

            $streams[] = $stream->getData()->serialize($serializer);
        }

        return $streams;
    }

    // -------------------------------------------------------------------------

    private function getDictValue(string $key): ?PdfObject
    {
        return $this->dictionary->get($key);
    }
}
