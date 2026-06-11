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
#[CoversMethod(PdfImage::class, 'hasMask')]
final class HasMaskTest extends TestCase
{
    #[Test]
    public function hasMaskReturnsFalseForOpaqueImage(): void
    {
        // Arrange — opaque JPEG
        $image = PdfImage::fromData(ImageFixtures::buildJpeg());

        // Act / Assert
        self::assertFalse($image->hasMask());
    }

    #[Test]
    public function hasMaskReturnsTrueForRgbaImage(): void
    {
        // Arrange — RGBA PNG has alpha channel → hasMask = true
        $image = PdfImage::fromData(ImageFixtures::buildRgbaPng());

        // Act / Assert
        self::assertTrue($image->hasMask());
    }
}
