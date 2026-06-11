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
#[CoversMethod(PdfImage::class, 'getData')]
final class GetDataTest extends TestCase
{
    #[Test]
    public function getDataReturnsRawBytesForJpeg(): void
    {
        // Arrange — for JPEG, getData() returns the original JPEG bytes
        $data = ImageFixtures::buildJpeg(20, 10, 3);
        $image = PdfImage::fromData($data);

        // Act / Assert
        self::assertSame($data, $image->getData());
    }
}
