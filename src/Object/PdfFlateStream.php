<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use RuntimeException;

/**
 * A zlib/Deflate-compressed PDF stream (FlateDecode filter).
 *
 * Compresses the supplied bytes with gzcompress() at construction time and
 * sets /Filter /FlateDecode in the stream dictionary. Use this when you need
 * a specific stream to be compressed independently of the document-wide
 * compression setting on PdfDocumentBuilder.
 *
 * For document-wide compression of all content streams, prefer
 * PdfDocumentBuilder::compress() instead.
 */
final class PdfFlateStream extends PdfStream
{
    public function __construct(string $data, int $level = 6)
    {
        $compressed = gzcompress($data, $level);

        if ($compressed === false) {
            throw new RuntimeException('Failed to compress stream data.');
        }

        parent::__construct(
            new PdfDictionary(['Filter' => new PdfName('FlateDecode')]),
            new PdfRawStreamData($compressed),
        );
    }
}
