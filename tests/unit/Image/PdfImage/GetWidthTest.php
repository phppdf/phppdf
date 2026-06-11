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
#[CoversMethod(PdfImage::class, 'getWidth')]
final class GetWidthTest extends TestCase
{
    #[Test]
    public function getWidthReturnsImageWidth(): void
    {
        // Arrange
        $image = PdfImage::fromData(ImageFixtures::buildJpeg(20, 10, 3));

        // Act / Assert
        self::assertSame(20, $image->getWidth());
    }
}
