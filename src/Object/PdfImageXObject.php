<?php

declare(strict_types=1);

namespace PhpPdf\Object;

/**
 * A PDF image XObject holding raw 8-bit-per-component DeviceRGB pixel data.
 *
 * For a higher-level API that handles JPEG and PNG files automatically
 * (including alpha / soft-mask support), use PdfImage together with
 * PdfPageBuilder::useImage() instead.
 */
final class PdfImageXObject extends PdfStream
{
    public function __construct(int $width, int $height, string $binaryData, string $colorSpace = 'DeviceRGB',)
    {
        parent::__construct(
            new PdfDictionary([
                'BitsPerComponent' => new PdfInteger(8),
                'ColorSpace' => new PdfName($colorSpace),
                'Height' => new PdfInteger($height),
                'Subtype' => new PdfName('Image'),
                'Type' => new PdfName('XObject'),
                'Width' => new PdfInteger($width),
            ]),
            new PdfRawStreamData($binaryData),
        );
    }
}
