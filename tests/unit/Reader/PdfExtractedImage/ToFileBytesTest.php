<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfExtractedImage;

use PhpPdf\Reader\PdfExtractedImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfExtractedImage::class)]
#[CoversMethod(PdfExtractedImage::class, 'toFileBytes')]
final class ToFileBytesTest extends TestCase
{
    #[Test]
    public function returnsRawJpegBytesForJpegImage(): void
    {
        // Arrange
        $jpegData = "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100);
        $image = new PdfExtractedImage(1, 'Im1', 1, 1, 'DeviceRGB', 8, $jpegData);

        // Act / Assert
        self::assertSame($jpegData, $image->toFileBytes());
    }

    #[Test]
    public function returnsPngForNonJpegImage(): void
    {
        // Arrange — 1×1 grey pixel
        $image = new PdfExtractedImage(1, 'Im1', 1, 1, 'DeviceGray', 8, "\x80");

        // Act
        $bytes = $image->toFileBytes();

        // Assert — PNG files start with the PNG magic signature
        self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $bytes);
    }
}
