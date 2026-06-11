<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfDocumentSerializer;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentSerializer::class)]
#[CoversMethod(PdfDocumentSerializer::class, 'writeDictionary')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteDictionaryTest extends TestCase
{
    #[Test]
    public function writeDictionaryEmpty(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        $serializer->writeDictionary(new PdfDictionary());

        // Assert
        self::assertSame("<<\n>>", $output->getContent());
    }

    #[Test]
    public function writeDictionaryWithEntry(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);
        $dictionary = new PdfDictionary(['Type' => new PdfName('Catalog')]);

        // Act
        $serializer->writeDictionary($dictionary);

        // Assert
        self::assertSame("<<\n/Type /Catalog\n>>", $output->getContent());
    }
}
