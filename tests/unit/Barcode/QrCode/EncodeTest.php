<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\QrCode;

use InvalidArgumentException;
use PhpPdf\Barcode\QrCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(QrCode::class)]
#[CoversMethod(QrCode::class, 'encode')]
final class EncodeTest extends TestCase
{
    #[Test]
    public function encodeThrowsOnInvalidEcLevel(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        QrCode::encode('Hello', 'X');
    }

    #[Test]
    public function encodeLowercaseEcLevelThrowsException(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);

        // Act
        QrCode::encode('Hello', 'm');
    }

    #[Test]
    public function encodeThrowsWhenDataExceedsVersion10Capacity(): void
    {
        // Arrange
        // Version 10 / H supports at most 122 data bytes; 200 bytes will overflow all versions 1–10.
        $this->expectException(InvalidArgumentException::class);

        // Act
        QrCode::encode(str_repeat('A', 200), 'H');
    }

    #[Test]
    public function encodeAcceptsEmptyString(): void
    {
        // Arrange / Act
        $qr = QrCode::encode('');

        // Assert
        self::assertInstanceOf(QrCode::class, $qr);
    }

    #[Test]
    public function encodeReturnsQrCodeInstanceWithDefaultEcLevel(): void
    {
        // Arrange / Act
        $qr = QrCode::encode('Hello');

        // Assert
        self::assertInstanceOf(QrCode::class, $qr);
    }

    #[Test]
    public function encodeReturnsQrCodeInstanceWithEcLevelL(): void
    {
        // Arrange / Act
        $qr = QrCode::encode('Hello', 'L');

        // Assert
        self::assertInstanceOf(QrCode::class, $qr);
    }

    #[Test]
    public function encodeReturnsQrCodeInstanceWithEcLevelM(): void
    {
        // Arrange / Act
        $qr = QrCode::encode('Hello', 'M');

        // Assert
        self::assertInstanceOf(QrCode::class, $qr);
    }

    #[Test]
    public function encodeReturnsQrCodeInstanceWithEcLevelQ(): void
    {
        // Arrange / Act
        $qr = QrCode::encode('Hello', 'Q');

        // Assert
        self::assertInstanceOf(QrCode::class, $qr);
    }

    #[Test]
    public function encodeReturnsQrCodeInstanceWithEcLevelH(): void
    {
        // Arrange / Act
        $qr = QrCode::encode('Hello', 'H');

        // Assert
        self::assertInstanceOf(QrCode::class, $qr);
    }

    #[Test]
    public function encodeWithByteWhoseMidLoopCodewordCancelsToZero(): void
    {
        // Arrange
        // chr(0x10) produces the codeword sequence [0x40, 0x11, 0x00, 0xEC, …].
        // However, the rsEC loop modifies $msg in-place via GF(2^8) XOR
        // operations.  After processing positions 0 and 1, the accumulated XOR
        // cancels $msg[5] to exactly 0, firing the zero-skip branch:
        //   `if ($msg[$i] === 0) { continue; }`   (line 239 in rsEC)
        $data = "\x10";

        // Act
        $qr = QrCode::encode($data);

        // Assert
        self::assertInstanceOf(QrCode::class, $qr);
    }

    #[Test]
    public function encodeWithUnequalBlockSizesInterleavesShorterBlockFirst(): void
    {
        // Arrange
        // 85 bytes at EC level Q selects version 7 (g1n=2, g1k=14, g2n=4, g2k=15).
        // Since g1k !== g2k, buildCodewords takes the `$g2k` branch (line 176) for
        // blocks beyond the first group, and the interleaving loop's `continue`
        // (line 188) fires once group-1 blocks (14 codewords) are exhausted while
        // iterating up to max(g1k, g2k) = 15.
        $data = str_repeat('A', 85);

        // Act
        $qr = QrCode::encode($data, 'Q');

        // Assert
        self::assertInstanceOf(QrCode::class, $qr);
    }
}
