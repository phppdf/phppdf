<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfFormXObject;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfFormXObject;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfRectangle;
use PhpPdf\Object\PdfStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFormXObject::class)]
#[CoversMethod(PdfFormXObject::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfRectangle::class)]
#[UsesClass(PdfStream::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $content = new PdfRawStreamData('');
        $bbox = new PdfRectangle(0, 0, 595, 842);

        // Act
        $obj = new PdfFormXObject($content, $bbox);

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }
}
