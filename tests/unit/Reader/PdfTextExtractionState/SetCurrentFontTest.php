<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfTextExtractionState;

use PhpPdf\Reader\PdfTextExtractionState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTextExtractionState::class)]
#[CoversMethod(PdfTextExtractionState::class, 'setCurrentFont')]
final class SetCurrentFontTest extends TestCase
{
    #[Test]
    public function storesFontName(): void
    {
        // Arrange
        $state = new PdfTextExtractionState();

        // Act
        $state->setCurrentFont('F1');

        // Assert
        self::assertSame('F1', $state->getCurrentFont());
    }

    #[Test]
    public function storesNull(): void
    {
        // Arrange
        $state = new PdfTextExtractionState();
        $state->setCurrentFont('F1');

        // Act
        $state->setCurrentFont(null);

        // Assert
        self::assertNull($state->getCurrentFont());
    }
}
