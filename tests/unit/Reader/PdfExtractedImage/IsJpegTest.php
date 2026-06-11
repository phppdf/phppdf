<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfExtractedImage;

use PhpPdf\Reader\PdfExtractedImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfExtractedImage::class)]
#[CoversMethod(PdfExtractedImage::class, 'isJpeg')]
final class IsJpegTest extends TestCase
{
    #[Test]
    public function returnsTrueForJpegMagicBytes(): void
    {
        // Arrange
        $image = new PdfExtractedImage(
            objectNumber: 1,
            name: 'Im1',
            width: 10,
            height: 10,
            colorSpace: 'DeviceRGB',
            bitsPerComponent: 8,
            data: "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100),
        );

        // Act / Assert
        self::assertTrue($image->isJpeg());
    }

    #[Test]
    public function returnsFalseForNonJpegData(): void
    {
        // Arrange
        $image = new PdfExtractedImage(
            objectNumber: 1,
            name: 'Im1',
            width: 1,
            height: 1,
            colorSpace: 'DeviceRGB',
            bitsPerComponent: 8,
            data: "\x00\x00\x00",
        );

        // Act / Assert
        self::assertFalse($image->isJpeg());
    }
}
