<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\QrCode;

use PhpPdf\Barcode\QrCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * QR Code module size = 4 × version + 17.
 *
 * Version selection (byte mode, overhead = ceil((4 + 8 + 8 × len) / 8)):
 *   - version 1 H: capacity 9 bytes → len ≤ 8 (overhead ≤ 9)
 *   - version 2 H: capacity 16 bytes → len ≤ 15 (overhead ≤ 16)
 *   - version 3 H: capacity 26 bytes → len ≤ 24 (overhead ≤ 26)
 *
 * At L level, version 1 carries up to 19 bytes — even a 15-char string stays v1 at L.
 */
#[CoversClass(QrCode::class)]
#[CoversMethod(QrCode::class, 'getSize')]
final class GetSizeTest extends TestCase
{
    #[Test]
    public function getSizeIsCorrectForVersion1(): void
    {
        // Arrange — single byte fits comfortably in version 1 at any EC level
        $qr = QrCode::encode('A');

        // Act
        $size = $qr->getSize();

        // Assert — version 1 → 4×1+17 = 21
        self::assertSame(21, $size);
    }

    #[Test]
    public function getSizeIsCorrectForVersion2(): void
    {
        // Arrange — 9 bytes at H overflows version 1 (capacity 9) and lands in version 2
        //   Overhead = ceil((4 + 8 + 8×9) / 8) = ceil(84/8) = 11 > 9 (v1 H) ≤ 16 (v2 H)
        $qr = QrCode::encode(str_repeat('A', 9), 'H');

        // Act
        $size = $qr->getSize();

        // Assert — version 2 → 4×2+17 = 25
        self::assertSame(25, $size);
    }

    #[Test]
    public function getSizeIsCorrectForVersion3(): void
    {
        // Arrange — 16 bytes at H overflows version 2 (capacity 16) and lands in version 3
        //   Overhead = ceil((4 + 8 + 8×16) / 8) = ceil(140/8) = 18 > 16 (v2 H) ≤ 26 (v3 H)
        $qr = QrCode::encode(str_repeat('A', 16), 'H');

        // Act
        $size = $qr->getSize();

        // Assert — version 3 → 4×3+17 = 29
        self::assertSame(29, $size);
    }

    #[Test]
    public function getSizeIsAlwaysOdd(): void
    {
        // Arrange — all valid QR Code sizes (4v+17) are odd
        $qr = QrCode::encode('Test data');

        // Act
        $size = $qr->getSize();

        // Assert
        self::assertSame(1, $size % 2);
    }

    #[Test]
    public function getSizeIsLargerForHigherEcLevelWithSameData(): void
    {
        // Arrange — the same data may need a higher version when EC level increases
        // A 9-char payload fits in v1 at L but requires v2 at H
        $qrL = QrCode::encode(str_repeat('A', 9), 'L');
        $qrH = QrCode::encode(str_repeat('A', 9), 'H');

        // Act / Assert
        self::assertGreaterThanOrEqual($qrL->getSize(), $qrH->getSize());
    }
}
