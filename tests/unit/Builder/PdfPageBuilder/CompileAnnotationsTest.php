<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'compileAnnotations')]
#[UsesClass(Color::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfString::class)]
final class CompileAnnotationsTest extends TestCase
{
    #[Test]
    public function compileAnnotationsWithNoAnnotationsReturnsEmptyArray(): void
    {
        $page = new PdfPageBuilder();
        $registry = new PdfObjectRegistry();

        $refs = $page->compileAnnotations($registry);

        self::assertSame([], $refs);
    }

    #[Test]
    public function compileAnnotationsWithAnnotationReturnsIndirectReferences(): void
    {
        $page = new PdfPageBuilder();
        $page->addTextAnnotation(50, 700, 'Note');

        $registry = new PdfObjectRegistry();
        $refs = $page->compileAnnotations($registry);

        self::assertCount(1, $refs);
        self::assertContainsOnlyInstancesOf(PdfIndirectReference::class, $refs);
    }
}
