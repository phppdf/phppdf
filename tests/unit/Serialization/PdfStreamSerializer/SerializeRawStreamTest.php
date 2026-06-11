<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfStreamSerializer;

use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Serialization\PdfStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfStreamSerializer::class)]
#[CoversMethod(PdfStreamSerializer::class, 'serializeRawStream')]
#[UsesClass(PdfRawStreamData::class)]
final class SerializeRawStreamTest extends TestCase
{
    #[Test]
    public function serializeRawStreamReturnsRawData(): void
    {
        // Arrange
        $serializer = new PdfStreamSerializer();
        $data = new PdfRawStreamData('raw content');

        // Act
        $result = $serializer->serializeRawStream($data);

        // Assert
        self::assertSame('raw content', $result);
    }
}
