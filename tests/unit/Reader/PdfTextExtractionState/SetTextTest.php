<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfTextExtractionState;

use PhpPdf\Reader\PdfTextExtractionState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTextExtractionState::class)]
#[CoversMethod(PdfTextExtractionState::class, 'setText')]
final class SetTextTest extends TestCase
{
    #[Test]
    public function storesText(): void
    {
        // Arrange
        $state = new PdfTextExtractionState();

        // Act
        $state->setText('Hello PDF');

        // Assert
        self::assertSame('Hello PDF', $state->getText());
    }

    #[Test]
    public function replacesExistingText(): void
    {
        // Arrange
        $state = new PdfTextExtractionState();
        $state->setText('old');

        // Act
        $state->setText('new');

        // Assert
        self::assertSame('new', $state->getText());
    }
}
