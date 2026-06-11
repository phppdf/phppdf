<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfType1Font;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfType1Font;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfType1Font::class)]
#[CoversMethod(PdfType1Font::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesWithDefaultFont(): void
    {
        // Arrange / Act
        $obj = new PdfType1Font();

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }

    #[Test]
    public function constructorAcceptsCustomBaseFont(): void
    {
        // Arrange / Act
        $obj = new PdfType1Font('Times-Roman');

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
