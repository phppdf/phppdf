<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfReal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'addSquareAnnotation')]
#[UsesClass(Color::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfReal::class)]
final class AddSquareAnnotationTest extends TestCase
{
    #[Test]
    public function addSquareAnnotationReturnsSelf(): void
    {
        // borderColor defaults to '#cc0000' inside addShapeAnnotation
        $page = new PdfPageBuilder();

        $result = $page->addSquareAnnotation(50, 700, 200, 100);

        self::assertSame($page, $result);
    }

    #[Test]
    public function addSquareAnnotationWithFillColorSetsIcEntry(): void
    {
        // Covers the `if ($fillColor !== null)` branch in addShapeAnnotation
        $page = new PdfPageBuilder();

        $result = $page->addSquareAnnotation(
            x: 50,
            y: 700,
            width: 200,
            height: 100,
            borderColor: Color::fromHex('#000000'),
            fillColor: Color::fromHex('#ffffff'),
        );

        self::assertSame($page, $result);
    }
}
