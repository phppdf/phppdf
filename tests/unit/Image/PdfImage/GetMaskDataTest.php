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
#[CoversMethod(PdfImage::class, 'getMaskData')]
final class GetMaskDataTest extends TestCase
{
    #[Test]
    public function getMaskDataReturnsNullForOpaqueImage(): void
    {
        // Arrange — JPEG has no alpha channel
        $image = PdfImage::fromData(ImageFixtures::buildJpeg());

        // Act / Assert
        self::assertNull($image->getMaskData());
    }

    #[Test]
    public function getMaskDataReturnsBytesForRgbaImage(): void
    {
        // Arrange — RGBA PNG has an alpha channel
        $image = PdfImage::fromData(ImageFixtures::buildRgbaPng(2, 2));

        // Act / Assert — 4 pixels, each gets one alpha byte in the mask
        $maskData = $image->getMaskData();
        self::assertNotNull($maskData);
        self::assertSame(4, strlen($maskData));
    }
}
