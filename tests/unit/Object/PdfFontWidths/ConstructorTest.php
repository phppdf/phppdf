<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfFontWidths;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfFontWidths;
use PhpPdf\Object\PdfInteger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFontWidths::class)]
#[CoversMethod(PdfFontWidths::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfInteger::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesEmptyArray(): void
    {
        // Arrange / Act
        $obj = new PdfFontWidths([]);

        // Assert
        self::assertInstanceOf(PdfArray::class, $obj);
    }

    #[Test]
    public function constructorCreatesArrayWithWidths(): void
    {
        // Arrange / Act
        $obj = new PdfFontWidths([600, 600, 333]);

        // Assert
        self::assertInstanceOf(PdfArray::class, $obj);
    }
}
