<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfExtractedImage;

use PhpPdf\Reader\PdfExtractedImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfExtractedImage::class)]
#[CoversMethod(PdfExtractedImage::class, 'toPng')]
final class ToPngTest extends TestCase
{
    #[Test]
    public function producesValidPngForGrayscalePixel(): void
    {
        // Arrange — 1×1 grayscale image (DeviceGray)
        $image = new PdfExtractedImage(1, 'Im1', 1, 1, 'DeviceGray', 8, "\x80");

        // Act
        $png = $image->toPng();

        // Assert — PNG magic header
        self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
        // IHDR chunk type at byte 12
        self::assertSame('IHDR', substr($png, 12, 4));
    }

    #[Test]
    public function producesValidPngForRgbPixel(): void
    {
        // Arrange — 1×1 RGB image (DeviceRGB)
        $image = new PdfExtractedImage(1, 'Im1', 1, 1, 'DeviceRGB', 8, "\xFF\x00\x00");

        // Act
        $png = $image->toPng();

        // Assert
        self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
    }

    #[Test]
    public function producesRgbaOutputWhenSmaskProvided(): void
    {
        // Arrange — 1×1 RGB with soft-mask alpha
        $image = new PdfExtractedImage(
            objectNumber: 1,
            name: 'Im1',
            width: 1,
            height: 1,
            colorSpace: 'DeviceRGB',
            bitsPerComponent: 8,
            data: "\xFF\x00\x00",
            smaskData: "\x80",
        );

        // Act
        $png = $image->toPng();

        // Assert — PNG color type 6 (RGBA) is stored as byte 25 in a standard 1x1 PNG
        self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
        // Parse IHDR: 4-byte length (13) + 'IHDR' + width + height + bit depth + color type
        $colorType = ord($png[25]);
        self::assertSame(6, $colorType); // color type 6 = RGBA
    }
}
