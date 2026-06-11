<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfTextExtractionState;

use PhpPdf\Reader\PdfTextExtractionState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTextExtractionState::class)]
final class ConstructTest extends TestCase
{
    #[Test]
    public function hasExpectedDefaults(): void
    {
        // Arrange / Act
        $state = new PdfTextExtractionState();

        // Assert
        self::assertSame('', $state->getText());
        self::assertFalse($state->isInText());
        self::assertNull($state->getCurrentFont());
        self::assertSame([1.0, 0.0, 0.0, 1.0, 0.0, 0.0], $state->getTextMatrix());
    }
}
