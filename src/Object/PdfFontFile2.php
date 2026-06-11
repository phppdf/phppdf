<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use RuntimeException;

use function gzcompress;

/**
 * An embedded TrueType font program stream, compressed with FlateDecode.
 *
 * Compresses the raw font binary with gzcompress() and records the uncompressed
 * byte length in the Length1 entry. Referenced from the FontFile2 entry of a
 * PdfFontDescriptor.
 */
final class PdfFontFile2 extends PdfStream
{
    public function __construct(string $fontBinary)
    {
        $compressed = gzcompress($fontBinary);

        if ($compressed === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Failed to compress font binary.');
            // @codeCoverageIgnoreEnd
        }

        parent::__construct(
            new PdfDictionary([
                'Filter' => new PdfName('FlateDecode'),
                'Length1' => new PdfInteger(strlen($fontBinary)),
            ]),
            new PdfRawStreamData($compressed),
        );
    }
}
