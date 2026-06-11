<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\Code128;

use InvalidArgumentException;
use PhpPdf\Barcode\Code128;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Code128::class)]
#[CoversMethod(Code128::class, 'encode')]
final class EncodeTest extends TestCase
{
    #[Test]
    public function encodeThrowsOnCharacterBelowAscii32(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        Code128::encode("\x1F");
    }

    #[Test]
    public function encodeThrowsOnCharacterAboveAscii127(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        Code128::encode("\x80");
    }

    #[Test]
    public function encodeAcceptsEmptyString(): void
    {
        // Arrange / Act
        $barcode = Code128::encode('');

        // Assert
        self::assertInstanceOf(Code128::class, $barcode);
    }

    #[Test]
    public function encodeAcceptsSpaceAsLowestValidCharacter(): void
    {
        // Arrange / Act
        $barcode = Code128::encode(' ');

        // Assert
        self::assertInstanceOf(Code128::class, $barcode);
    }

    #[Test]
    public function encodeAcceptsDelAsHighestValidCharacter(): void
    {
        // Arrange / Act
        $barcode = Code128::encode("\x7F");

        // Assert
        self::assertInstanceOf(Code128::class, $barcode);
    }
}
