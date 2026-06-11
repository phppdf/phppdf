<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfString;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeString')]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteStringTest extends TestCase
{
    #[Test]
    public function writeStringWrapsValueInParentheses(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act (currentObjectNumber=0, so shouldEncrypt() returns false)
        $serializer->writeString(new PdfString('hello'));

        // Assert
        self::assertSame('(hello)', $output->getContent());
    }

    #[Test]
    public function writeStringEscapesBackslash(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeString(new PdfString('a\\b'));

        // Assert — backslash becomes two backslashes in the PDF literal string
        self::assertSame('(a\\\\b)', $output->getContent());
    }

    #[Test]
    public function writeStringEscapesParentheses(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeString(new PdfString('(test)'));

        // Assert
        self::assertSame('(\\(test\\))', $output->getContent());
    }

    #[Test]
    public function writeStringEscapesCarriageReturn(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeString(new PdfString("line\r"));

        // Assert — carriage return becomes the two-character sequence \r in the PDF literal
        self::assertSame('(line\\r)', $output->getContent());
    }
}
