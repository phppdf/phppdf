<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfIndirectReference;

use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfIndirectReference::class)]
#[CoversMethod(PdfIndirectReference::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeWritesReferenceToken(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfIndirectReference(3, 0))->serialize($serializer);

        // Assert
        self::assertSame('3 0 R', $output->getContent());
    }
}
