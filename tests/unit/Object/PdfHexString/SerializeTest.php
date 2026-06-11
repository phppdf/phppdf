<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfHexString;

use PhpPdf\Object\PdfHexString;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfHexString::class)]
#[CoversMethod(PdfHexString::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeWritesHexString(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfHexString("\x48\x65\x6C\x6C\x6F"))->serialize($serializer);

        // Assert
        self::assertSame('<48656C6C6F>', $output->getContent());
    }
}
