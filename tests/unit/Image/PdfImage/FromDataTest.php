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
#[CoversMethod(PdfImage::class, 'fromData')]
final class FromDataTest extends TestCase
{
    // -------------------------------------------------------------------------
    // JPEG paths
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataParsesRgbJpeg(): void
    {
        // Arrange — 3 components → DeviceRGB
        $data = ImageFixtures::buildJpeg(20, 10, 3);

        // Act
        $image = PdfImage::fromData($data);

        // Assert
        self::assertSame(20, $image->getWidth());
        self::assertSame(10, $image->getHeight());
        self::assertSame('DeviceRGB', $image->getColorSpace());
        self::assertTrue($image->isJpeg());
        self::assertFalse($image->hasMask());
        self::assertNull($image->getMaskData());
        self::assertSame($data, $image->getData());
    }

    #[Test]
    public function fromDataParsesGrayscaleJpeg(): void
    {
        // Arrange — 1 component → DeviceGray
        $data = ImageFixtures::buildJpeg(8, 6, 1);

        // Act
        $image = PdfImage::fromData($data);

        // Assert
        self::assertSame('DeviceGray', $image->getColorSpace());
    }

    #[Test]
    public function fromDataParsesCmykJpeg(): void
    {
        // Arrange — 4 components → DeviceCMYK
        $data = ImageFixtures::buildJpeg(4, 4, 4);

        // Act
        $image = PdfImage::fromData($data);

        // Assert
        self::assertSame('DeviceCMYK', $image->getColorSpace());
    }

    #[Test]
    public function fromDataHandlesStandaloneMarkerInJpeg(): void
    {
        // Arrange — RST0 (0xFFD0) standalone marker before SOF
        $data = ImageFixtures::buildJpegWithStandaloneMarker();

        // Act
        $image = PdfImage::fromData($data);

        // Assert — parsed successfully despite standalone marker
        self::assertSame('DeviceRGB', $image->getColorSpace());
    }

    #[Test]
    public function fromDataHandlesCustomMarkerBeforeSofInJpeg(): void
    {
        // Arrange — APP1 custom segment before SOF (exercises segLen skip path)
        $data = ImageFixtures::buildJpegWithCustomMarkerBeforeSof();

        // Act
        $image = PdfImage::fromData($data);

        // Assert
        self::assertSame(10, $image->getWidth());
    }

    #[Test]
    public function fromDataThrowsForMalformedJpeg(): void
    {
        // Arrange — first marker has non-0xFF byte
        $data = ImageFixtures::buildMalformedJpeg();

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Malformed JPEG');

        // Act
        PdfImage::fromData($data);
    }

    #[Test]
    public function fromDataThrowsWhenNoSofMarkerFound(): void
    {
        // Arrange — valid-looking JPEG with no SOF segment
        $data = ImageFixtures::buildJpegWithoutSof();

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find JPEG SOF marker');

        // Act
        PdfImage::fromData($data);
    }

    #[Test]
    public function fromDataThrowsWhenMarkerSegmentLengthIsTruncated(): void
    {
        // Arrange — non-SOF marker (DQT) with no length bytes (pos + 3 >= len)
        $data = ImageFixtures::buildJpegWithMarkerTooCloseToEnd();

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find JPEG SOF marker');

        // Act
        PdfImage::fromData($data);
    }

    #[Test]
    public function fromDataThrowsWhenSofIsTruncated(): void
    {
        // Arrange — SOF0 marker at the very end (pos + 9 >= len)
        $data = ImageFixtures::buildJpegWithTruncatedSof();

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find JPEG SOF marker');

        // Act
        PdfImage::fromData($data);
    }

    // -------------------------------------------------------------------------
    // PNG paths
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataParsesRgbPng(): void
    {
        // Arrange — RGB PNG, colorType=2
        $data = ImageFixtures::buildRgbPng(4, 4);

        // Act
        $image = PdfImage::fromData($data);

        // Assert
        self::assertSame(4, $image->getWidth());
        self::assertSame(4, $image->getHeight());
        self::assertSame('DeviceRGB', $image->getColorSpace());
        self::assertFalse($image->isJpeg());
        self::assertFalse($image->hasMask());
        self::assertNull($image->getMaskData());
        // Raw pixel data for 4×4 RGB = 48 bytes
        self::assertSame(4 * 4 * 3, strlen($image->getData()));
    }

    #[Test]
    public function fromDataParsesRgbaPng(): void
    {
        // Arrange — RGBA PNG, colorType=6 (hasAlpha=true)
        $data = ImageFixtures::buildRgbaPng(2, 2);

        // Act
        $image = PdfImage::fromData($data);

        // Assert
        self::assertSame('DeviceRGB', $image->getColorSpace());
        self::assertFalse($image->isJpeg());
        self::assertTrue($image->hasMask());
        self::assertNotNull($image->getMaskData());
        // maskData = 4 pixels × 1 byte each
        self::assertSame(4, strlen($image->getMaskData()));
    }

    #[Test]
    public function fromDataParsesGrayscalePng(): void
    {
        // Arrange — grayscale PNG, colorType=0
        $data = ImageFixtures::buildGrayscalePng(2, 2);

        // Act
        $image = PdfImage::fromData($data);

        // Assert
        self::assertSame('DeviceGray', $image->getColorSpace());
        self::assertFalse($image->hasMask());
        // 4 pixels × 1 byte each
        self::assertSame(4, strlen($image->getData()));
    }

    #[Test]
    public function fromDataParsesGrayAlphaPng(): void
    {
        // Arrange — gray+alpha PNG, colorType=4 (isGray=true, hasAlpha=true)
        $data = ImageFixtures::buildGrayAlphaPng(2, 2);

        // Act
        $image = PdfImage::fromData($data);

        // Assert
        self::assertSame('DeviceGray', $image->getColorSpace());
        self::assertTrue($image->hasMask());
        self::assertNotNull($image->getMaskData());
    }

    #[Test]
    public function fromDataThrowsWhenGdFailsToDecodePng(): void
    {
        // Arrange — PNG magic bytes + garbage → imagecreatefromstring() returns false
        $data = ImageFixtures::buildCorruptedPng();

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to decode PNG image data');

        // Act
        PdfImage::fromData($data);
    }

    #[Test]
    public function fromDataThrowsWhenPngIsTooShortForColorType(): void
    {
        // Arrange — PNG magic bytes followed by too little data to read the
        // colorType byte at offset 25 → defaults to colorType=2 (RGB)
        $data = ImageFixtures::buildTruncatedPng();

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to decode PNG image data');

        // Act
        PdfImage::fromData($data);
    }

    // -------------------------------------------------------------------------
    // Unsupported format
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDataThrowsForUnsupportedFormat(): void
    {
        // Arrange — GIF data
        $data = ImageFixtures::buildUnsupportedImageData();

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported image format');

        // Act
        PdfImage::fromData($data);
    }
}
