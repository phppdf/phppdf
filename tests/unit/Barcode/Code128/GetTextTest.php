<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\Code128;

use PhpPdf\Barcode\Code128;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Code128::class)]
#[CoversMethod(Code128::class, 'getText')]
final class GetTextTest extends TestCase
{
    #[Test]
    public function getTextReturnsOriginalEncodedString(): void
    {
        // Arrange
        $barcode = Code128::encode('Hello, World!');

        // Act
        $text = $barcode->getText();

        // Assert
        self::assertSame('Hello, World!', $text);
    }

    #[Test]
    public function getTextReturnsEmptyStringForEmptyInput(): void
    {
        // Arrange
        $barcode = Code128::encode('');

        // Act
        $text = $barcode->getText();

        // Assert
        self::assertSame('', $text);
    }
}
