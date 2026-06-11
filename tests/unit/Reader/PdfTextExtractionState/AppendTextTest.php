<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfTextExtractionState;

use PhpPdf\Reader\PdfTextExtractionState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTextExtractionState::class)]
#[CoversMethod(PdfTextExtractionState::class, 'appendText')]
final class AppendTextTest extends TestCase
{
    #[Test]
    public function appendsToEmptyText(): void
    {
        // Arrange
        $state = new PdfTextExtractionState();

        // Act
        $state->appendText('Hello');

        // Assert
        self::assertSame('Hello', $state->getText());
    }

    #[Test]
    public function appendsToExistingText(): void
    {
        // Arrange
        $state = new PdfTextExtractionState();
        $state->setText('Hello');

        // Act
        $state->appendText(' World');

        // Assert
        self::assertSame('Hello World', $state->getText());
    }
}
