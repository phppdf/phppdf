<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfEmbeddedFile;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfEmbeddedFile;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfEmbeddedFile::class)]
#[CoversMethod(PdfEmbeddedFile::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $data = new PdfRawStreamData('file content');

        // Act
        $obj = new PdfEmbeddedFile($data);

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }
}
