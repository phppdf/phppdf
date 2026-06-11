<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfHexString;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeHexString')]
#[UsesClass(PdfHexString::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteHexStringTest extends TestCase
{
    #[Test]
    public function writeHexStringOutputsUppercaseHex(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act (currentObjectNumber=0, so shouldEncrypt() returns false)
        $serializer->writeHexString(new PdfHexString('Hello'));

        // Assert
        self::assertSame('<48656C6C6F>', $output->getContent());
    }

    #[Test]
    public function writeHexStringWithEmptyBinaryOutputsEmptyAngleBrackets(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeHexString(new PdfHexString(''));

        // Assert
        self::assertSame('<>', $output->getContent());
    }
}
