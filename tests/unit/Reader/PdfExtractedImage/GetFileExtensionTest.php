<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfExtractedImage;

use PhpPdf\Reader\PdfExtractedImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfExtractedImage::class)]
#[CoversMethod(PdfExtractedImage::class, 'getFileExtension')]
final class GetFileExtensionTest extends TestCase
{
    #[Test]
    public function returnsJpgForJpegData(): void
    {
        // Arrange
        $image = new PdfExtractedImage(1, 'Im1', 1, 1, 'DeviceRGB', 8, "\xFF\xD8\xFF\xE0" . str_repeat("\x00", 100));

        // Act / Assert
        self::assertSame('jpg', $image->getFileExtension());
    }

    #[Test]
    public function returnsPngForNonJpegData(): void
    {
        // Arrange
        $image = new PdfExtractedImage(1, 'Im1', 1, 1, 'DeviceRGB', 8, "\x00\x00\x00");

        // Act / Assert
        self::assertSame('png', $image->getFileExtension());
    }
}
