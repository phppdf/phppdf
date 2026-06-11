<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\ConcatenateMatrix;

use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\ConcatenateMatrix;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConcatenateMatrix::class)]
#[CoversMethod(ConcatenateMatrix::class, 'serialize')]
#[UsesClass(PdfContentStreamSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
#[UsesClass(Matrix::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeProducesExpectedOutput(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfContentStreamSerializer($output);
        $op = new ConcatenateMatrix(Matrix::identity());

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("1 0 0 1 0 0 cm
", $output->getContent());
    }
}
