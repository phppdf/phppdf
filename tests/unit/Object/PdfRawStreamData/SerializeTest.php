<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfRawStreamData;

use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Serialization\PdfStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfRawStreamData::class)]
#[CoversMethod(PdfRawStreamData::class, 'serialize')]
#[UsesClass(PdfStreamSerializer::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeReturnsRawData(): void
    {
        // Arrange
        $serializer = new PdfStreamSerializer();

        // Act
        $result = (new PdfRawStreamData('raw bytes'))->serialize($serializer);

        // Assert
        self::assertSame('raw bytes', $result);
    }
}
