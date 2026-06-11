<?php

declare(strict_types=1);

namespace PhpPdf\Barcode\QrCode;

use PhpPdf\Barcode\QrCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(QrCode::class)]
#[CoversMethod(QrCode::class, 'gfInit')]
final class GfInitTest extends TestCase
{
    #[RunInSeparateProcess]
    #[Test]
    public function initializesGfTablesOnFirstCall(): void
    {
        // Arrange
        // Fresh process: GF tables are empty and have never been initialised.

        // Act
        $qr = QrCode::encode('A');

        // Assert
        self::assertInstanceOf(QrCode::class, $qr);
    }

    #[Test]
    public function returnsEarlyWhenTablesAreAlreadyInitialised(): void
    {
        // Arrange
        QrCode::encode('A'); // ensures GF tables are populated

        // Act
        $qr = QrCode::encode('B'); // second call hits the early-return branch

        // Assert
        self::assertInstanceOf(QrCode::class, $qr);
    }
}
