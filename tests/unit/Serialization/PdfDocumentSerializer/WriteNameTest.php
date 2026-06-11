<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfName;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeName')]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteNameTest extends TestCase
{
    #[Test]
    public function writeNamePrefixesSlash(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeName(new PdfName('Type'));

        // Assert
        self::assertSame('/Type', $output->getContent());
    }

    #[Test]
    public function writeNamePreservesValue(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeName(new PdfName('FlateDecode'));

        // Assert
        self::assertSame('/FlateDecode', $output->getContent());
    }
}
