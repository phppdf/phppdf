<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\EAN13;

use InvalidArgumentException;
use PhpPdf\Barcode\EAN13;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EAN13::class)]
#[CoversMethod(EAN13::class, 'encode')]
final class EncodeTest extends TestCase
{
    #[Test]
    public function encodeThrowsOnFewerThanTwelveDigits(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        EAN13::encode('12345678901'); // 11 digits
    }

    #[Test]
    public function encodeThrowsOnMoreThanThirteenDigits(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        EAN13::encode('12345678901234'); // 14 digits
    }

    #[Test]
    public function encodeThrowsOnIncorrectCheckDigit(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        EAN13::encode('5901234123456'); // correct check digit is 7, not 6
    }

    #[Test]
    public function encodeAcceptsTwelveDigitInput(): void
    {
        // Arrange / Act
        $barcode = EAN13::encode('590123412345');

        // Assert
        self::assertInstanceOf(EAN13::class, $barcode);
    }

    #[Test]
    public function encodeAcceptsValidThirteenDigitInput(): void
    {
        // Arrange / Act
        $barcode = EAN13::encode('5901234123457');

        // Assert
        self::assertInstanceOf(EAN13::class, $barcode);
    }

    #[Test]
    public function encodeStripsNonDigitCharacters(): void
    {
        // Arrange / Act
        $barcode = EAN13::encode('590-123-412345'); // dashes stripped → 12 digits

        // Assert
        self::assertInstanceOf(EAN13::class, $barcode);
    }
}
