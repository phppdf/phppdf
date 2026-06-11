<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfString;

use PhpPdf\Object\PdfString;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfString::class)]
#[CoversMethod(PdfString::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeWritesLiteralString(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfString('hello'))->serialize($serializer);

        // Assert
        self::assertSame('(hello)', $output->getContent());
    }

    #[Test]
    public function serializeEscapesParentheses(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfString('(a)'))->serialize($serializer);

        // Assert
        self::assertSame('(\\(a\\))', $output->getContent());
    }
}
