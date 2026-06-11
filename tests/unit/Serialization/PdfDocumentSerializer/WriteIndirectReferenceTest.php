<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeIndirectReference')]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteIndirectReferenceTest extends TestCase
{
    #[Test]
    public function writeIndirectReferenceFormatsAsObjectGenerationR(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeIndirectReference(new PdfIndirectReference(1, 0));

        // Assert
        self::assertSame('1 0 R', $output->getContent());
    }

    #[Test]
    public function writeIndirectReferenceWithNonZeroGeneration(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeIndirectReference(new PdfIndirectReference(5, 2));

        // Assert
        self::assertSame('5 2 R', $output->getContent());
    }
}
