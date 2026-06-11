<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\EAN13;

use PhpPdf\Barcode\EAN13;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EAN13::class)]
#[CoversMethod(EAN13::class, 'getText')]
final class GetTextTest extends TestCase
{
    #[Test]
    public function getTextReturnsFullThirteenDigitString(): void
    {
        // Arrange
        $barcode = EAN13::encode('5901234123457');

        // Act
        $text = $barcode->getText();

        // Assert
        self::assertSame('5901234123457', $text);
        self::assertSame(13, strlen($text));
    }

    #[Test]
    public function getTextIncludesComputedCheckDigitForTwelveDigitInput(): void
    {
        // Arrange
        $barcode = EAN13::encode('590123412345'); // check digit is 7

        // Act
        $text = $barcode->getText();

        // Assert
        self::assertSame('5901234123457', $text);
    }
}
