<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfBoolean;

use PhpPdf\Object\PdfBoolean;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfBoolean::class)]
#[CoversMethod(PdfBoolean::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeTrueWritesTrue(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfBoolean(true))->serialize($serializer);

        // Assert
        self::assertSame('true', $output->getContent());
    }

    #[Test]
    public function serializeFalseWritesFalse(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfBoolean(false))->serialize($serializer);

        // Assert
        self::assertSame('false', $output->getContent());
    }
}
