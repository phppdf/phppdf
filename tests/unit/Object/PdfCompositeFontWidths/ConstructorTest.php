<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfCompositeFontWidths;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfCompositeFontWidths;
use PhpPdf\Object\PdfInteger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfCompositeFontWidths::class)]
#[CoversMethod(PdfCompositeFontWidths::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfInteger::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesEmptyArray(): void
    {
        // Arrange / Act
        $obj = new PdfCompositeFontWidths([]);

        // Assert
        self::assertInstanceOf(PdfArray::class, $obj);
    }

    #[Test]
    public function constructorCreatesArrayWithEntries(): void
    {
        // Arrange / Act
        $obj = new PdfCompositeFontWidths([65 => 600, 66 => 600]);

        // Assert
        self::assertInstanceOf(PdfArray::class, $obj);
    }
}
