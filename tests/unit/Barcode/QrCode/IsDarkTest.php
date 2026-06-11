<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\QrCode;

use PhpPdf\Barcode\QrCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * All tests use a version-1 QR Code (21×21) unless otherwise noted.
 *
 * Fixed / function-module positions referenced below (never masked):
 *   - Finder pattern top-left: rows 0–6, cols 0–6 (all four corners → dark)
 *   - Finder pattern top-right: rows 0–6, cols 14–20 (top-left of that block = (0, 14) → dark)
 *   - Finder pattern bottom-left: rows 14–20, cols 0–6 ((14, 0) → dark)
 *   - Separator top-left row: row 7, cols 0–7 → always light
 *   - Timing row 6: col 8 (i=8, even) → dark; col 9 (i=9, odd) → light
 *   - Dark module (v1): row 13, col 8 → always dark
 */
#[CoversClass(QrCode::class)]
#[CoversMethod(QrCode::class, 'isDark')]
final class IsDarkTest extends TestCase
{
    private QrCode $qr;

    #[Test]
    public function isDarkReturnsTrueForTopLeftFinderCorner(): void
    {
        // Arrange — (0, 0) is the top-left corner of the top-left finder pattern
        // Act / Assert
        self::assertTrue($this->qr->isDark(0, 0));
    }

    #[Test]
    public function isDarkReturnsTrueForTopLeftFinderOppositeCorner(): void
    {
        // Arrange — (6, 6) is the bottom-right corner of the top-left finder pattern
        // Act / Assert
        self::assertTrue($this->qr->isDark(6, 6));
    }

    #[Test]
    public function isDarkReturnsTrueForTopRightFinderCorner(): void
    {
        // Arrange — for a 21-module QR Code, top-right finder starts at col 14 (= 21 - 7)
        // Act / Assert
        self::assertTrue($this->qr->isDark(0, 14));
    }

    #[Test]
    public function isDarkReturnsTrueForBottomLeftFinderCorner(): void
    {
        // Arrange — for a 21-module QR Code, bottom-left finder starts at row 14 (= 21 - 7)
        // Act / Assert
        self::assertTrue($this->qr->isDark(14, 0));
    }

    #[Test]
    public function isDarkReturnsFalseForSeparatorRow(): void
    {
        // Arrange — row 7, col 0 is in the separator strip between the top-left finder and data
        // Act / Assert
        self::assertFalse($this->qr->isDark(7, 0));
    }

    #[Test]
    public function isDarkReturnsFalseForSeparatorColumn(): void
    {
        // Arrange — row 0, col 7 is in the separator column beside the top-left finder
        // Act / Assert
        self::assertFalse($this->qr->isDark(0, 7));
    }

    #[Test]
    public function isDarkReturnsTrueForDarkModule(): void
    {
        // Arrange — the "dark module" is always placed at (4 × version + 9, 8).
        //   For version 1: row = 4×1+9 = 13, col = 8.
        // Act / Assert
        self::assertTrue($this->qr->isDark(13, 8));
    }

    #[Test]
    public function isDarkReturnsTrueForEvenTimingModule(): void
    {
        // Arrange — timing strip at row 6; col 8 (even index) is always dark
        // Act / Assert
        self::assertTrue($this->qr->isDark(6, 8));
    }

    #[Test]
    public function isDarkReturnsFalseForOddTimingModule(): void
    {
        // Arrange — timing strip at row 6; col 9 (odd index) is always light
        // Act / Assert
        self::assertFalse($this->qr->isDark(6, 9));
    }

    #[Test]
    public function isDarkReturnsTrueForVerticalEvenTimingModule(): void
    {
        // Arrange — timing strip at col 6; row 8 (even index) is always dark
        // Act / Assert
        self::assertTrue($this->qr->isDark(8, 6));
    }

    #[Test]
    public function isDarkReturnsFalseForVerticalOddTimingModule(): void
    {
        // Arrange — timing strip at col 6; row 9 (odd index) is always light
        // Act / Assert
        self::assertFalse($this->qr->isDark(9, 6));
    }

    protected function setUp(): void
    {
        // Single-byte payload → version 1, size 21
        $this->qr = QrCode::encode('A');
    }
}
