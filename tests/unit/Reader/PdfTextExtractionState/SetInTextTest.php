<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfTextExtractionState;

use PhpPdf\Reader\PdfTextExtractionState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTextExtractionState::class)]
#[CoversMethod(PdfTextExtractionState::class, 'setInText')]
final class SetInTextTest extends TestCase
{
    #[Test]
    public function storesTrue(): void
    {
        // Arrange
        $state = new PdfTextExtractionState();

        // Act
        $state->setInText(true);

        // Assert
        self::assertTrue($state->isInText());
    }

    #[Test]
    public function storesFalse(): void
    {
        // Arrange
        $state = new PdfTextExtractionState();
        $state->setInText(true);

        // Act
        $state->setInText(false);

        // Assert
        self::assertFalse($state->isInText());
    }
}
