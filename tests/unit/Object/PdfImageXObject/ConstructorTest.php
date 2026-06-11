<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfImageXObject;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfImageXObject;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfImageXObject::class)]
#[CoversMethod(PdfImageXObject::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange / Act
        $obj = new PdfImageXObject(10, 10, str_repeat("\xFF", 300));

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }
}
