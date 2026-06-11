<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfDictionary;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDictionary::class)]
#[CoversMethod(PdfDictionary::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
#[UsesClass(PdfName::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeEmptyDictionaryWritesDelimiters(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfDictionary())->serialize($serializer);

        // Assert
        self::assertSame("<<\n>>", $output->getContent());
    }

    #[Test]
    public function serializeWritesKeyValuePairs(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfDictionary(['Type' => new PdfName('Page')]))->serialize($serializer);

        // Assert
        self::assertSame("<<\n/Type /Page\n>>", $output->getContent());
    }
}
