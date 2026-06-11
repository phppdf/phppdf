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
#[CoversMethod(PdfPageBuilder::class, 'addCircleAnnotation')]
#[UsesClass(Color::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfReal::class)]
final class AddCircleAnnotationTest extends TestCase
{
    #[Test]
    public function addCircleAnnotationReturnsSelf(): void
    {
        $page = new PdfPageBuilder();

        $result = $page->addCircleAnnotation(50, 700, 200, 100);

        self::assertSame($page, $result);
    }

    #[Test]
    public function addCircleAnnotationWithFillColor(): void
    {
        $page = new PdfPageBuilder();

        $result = $page->addCircleAnnotation(
            x: 50,
            y: 700,
            width: 200,
            height: 100,
            borderColor: Color::fromHex('#333333'),
            fillColor: Color::fromHex('#cccccc'),
        );

        self::assertSame($page, $result);
    }
}
