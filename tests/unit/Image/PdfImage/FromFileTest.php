<?php

declare(strict_types=1);

namespace PhpPdf\Image\PdfImage;

use PhpPdf\Image\ImageFixtures;
use PhpPdf\Image\PdfImage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PdfImage::class)]
#[CoversMethod(PdfImage::class, 'fromFile')]
final class FromFileTest extends TestCase
{
    #[Test]
    public function fromFileReturnsPdfImageForReadableFile(): void
    {
        // Arrange — write a minimal JPEG to a temp file
        $path = tempnam(sys_get_temp_dir(), 'pdfimage_') . '.jpg';
        file_put_contents($path, ImageFixtures::buildJpeg());

        try {
            // Act
            $image = PdfImage::fromFile($path);

            // Assert
            self::assertInstanceOf(PdfImage::class, $image);
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function fromFileThrowsForUnreadablePath(): void
    {
        // Arrange
        $path = '/nonexistent/path/image.jpg';

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Image file not readable:');

        // Act
        PdfImage::fromFile($path);
    }
}
