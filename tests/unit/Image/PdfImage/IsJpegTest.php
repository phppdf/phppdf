<?php

declare(strict_types=1);

namespace PhpPdf\Image\PdfImage;

use PhpPdf\Image\ImageFixtures;
use PhpPdf\Image\PdfImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfImage::class)]
#[CoversMethod(PdfImage::class, 'isJpeg')]
final class IsJpegTest extends TestCase
{
    #[Test]
    public function isJpegReturnsTrueForJpegData(): void
    {
        // Arrange
        $image = PdfImage::fromData(ImageFixtures::buildJpeg());

        // Act / Assert
        self::assertTrue($image->isJpeg());
    }

    #[Test]
    public function isJpegReturnsFalseForPngData(): void
    {
        // Arrange
        $image = PdfImage::fromData(ImageFixtures::buildRgbPng());

        // Act / Assert
        self::assertFalse($image->isJpeg());
    }
}
