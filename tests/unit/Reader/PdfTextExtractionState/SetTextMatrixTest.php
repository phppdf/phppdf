<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfTextExtractionState;

use PhpPdf\Reader\PdfTextExtractionState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTextExtractionState::class)]
#[CoversMethod(PdfTextExtractionState::class, 'setTextMatrix')]
final class SetTextMatrixTest extends TestCase
{
    #[Test]
    public function storesTextMatrix(): void
    {
        // Arrange
        $state = new PdfTextExtractionState();
        $matrix = [1.0, 0.0, 0.0, 1.0, 72.0, 700.0];

        // Act
        $state->setTextMatrix($matrix);

        // Assert
        self::assertSame($matrix, $state->getTextMatrix());
    }
}
