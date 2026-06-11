<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfToUnicodeCMap;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfToUnicodeCMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfToUnicodeCMap::class)]
#[CoversMethod(PdfToUnicodeCMap::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesStreamWithEmptyGlyphs(): void
    {
        // Arrange / Act
        $obj = new PdfToUnicodeCMap([]);

        // Assert
        $rawData = $obj->getData();
        self::assertInstanceOf(PdfRawStreamData::class, $rawData);

        $content = gzuncompress($rawData->getData());
        self::assertIsString($content);
        self::assertStringContainsString('endcmap', $content);
        self::assertStringNotContainsString('beginbfchar', $content);
    }

    #[Test]
    public function constructorCreatesStreamWithGlyphs(): void
    {
        // Arrange / Act
        $obj = new PdfToUnicodeCMap([0x0041 => 65, 0x0042 => 66]);

        // Assert
        $rawData = $obj->getData();
        self::assertInstanceOf(PdfRawStreamData::class, $rawData);

        $content = gzuncompress($rawData->getData());
        self::assertIsString($content);
        self::assertStringContainsString('2 beginbfchar', $content);
        self::assertStringContainsString('<0041> <0041>', $content);
        self::assertStringContainsString('<0042> <0042>', $content);
    }
}
