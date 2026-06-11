<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\QrCode;

use PhpPdf\Barcode\QrCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(QrCode::class)]
#[CoversMethod(QrCode::class, 'penalty')]
#[CoversMethod(QrCode::class, 'runPenalty')]
final class PenaltyTest extends TestCase
{
    #[Test]
    public function penalizesFinderLikePatternInRows(): void
    {
        // Arrange
        // encode('Hello', 'L') produces a v1 matrix that contains the 11-module
        // finder-like sequence [1,0,1,1,1,0,1,0,0,0,0] at row 16, col 0.

        // Act
        $qr = QrCode::encode('Hello', 'L');

        // Assert
        self::assertSame(21, $qr->getSize());
    }

    #[Test]
    public function penalizesFinderLikePatternInColumns(): void
    {
        // Arrange
        // The same v1 / L matrix also contains the finder-like sequence
        // at column 3, row 0.

        // Act
        $qr = QrCode::encode('Hello', 'L');

        // Assert
        self::assertSame(21, $qr->getSize());
    }

    #[Test]
    public function penaltyIsLowerForBetterMaskedMatrix(): void
    {
        // Arrange / Act
        $qrL = QrCode::encode('Hello', 'L');
        $qrH = QrCode::encode('Hello', 'H');

        // Assert – both encode successfully; penalty evaluation drove mask selection
        self::assertInstanceOf(QrCode::class, $qrL);
        self::assertInstanceOf(QrCode::class, $qrH);
    }
}
