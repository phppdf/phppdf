<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfRawObject;

use PhpPdf\Object\PdfRawObject;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfRawObject::class)]
#[CoversMethod(PdfRawObject::class, 'serialize')]
#[UsesClass(PdfDocumentSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeWritesRawContentVerbatim(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfDocumentSerializer($output);

        // Act
        (new PdfRawObject('[0 0000000000 0000000000 0000000000]'))->serialize($serializer);

        // Assert
        self::assertSame('[0 0000000000 0000000000 0000000000]', $output->getContent());
    }
}
