<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfNull;

use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfRawObject;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfNull::class)]
#[CoversMethod(PdfNull::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
#[UsesClass(PdfRawObject::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeWritesNullLiteral(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfNull())->serialize($serializer);

        // Assert
        self::assertSame('null', $output->getContent());
    }
}
