<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfFlateStream;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfFlateStream;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFlateStream::class)]
#[CoversMethod(PdfFlateStream::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesCompressedStream(): void
    {
        // Arrange / Act
        $obj = new PdfFlateStream('Hello, World!');

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }
}
