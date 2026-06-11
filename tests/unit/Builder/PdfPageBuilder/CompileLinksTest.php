<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfLinkSpec;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDestination;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfGoToAction;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfLinkAnnotation;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfUriAction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'compileLinks')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDestination::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfGoToAction::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfLinkAnnotation::class)]
#[UsesClass(PdfLinkSpec::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfUriAction::class)]
final class CompileLinksTest extends TestCase
{
    #[Test]
    public function compileLinksWithNoLinksReturnsEmptyArray(): void
    {
        $page = new PdfPageBuilder();
        $registry = new PdfObjectRegistry();

        $refs = $page->compileLinks($registry, []);

        self::assertSame([], $refs);
    }

    #[Test]
    public function compileLinksWithUriLinkRegistersUriAction(): void
    {
        $page = new PdfPageBuilder();
        $page->addUriLink(50, 700, 100, 20, 'https://example.com');

        $registry = new PdfObjectRegistry();
        $refs = $page->compileLinks($registry, []);

        self::assertCount(1, $refs);
        self::assertContainsOnlyInstancesOf(PdfIndirectReference::class, $refs);
    }

    #[Test]
    public function compileLinksWithPageLinkRegistersGoToAction(): void
    {
        // Covers the `$spec->uri === null` branch (GoTo action)
        $page = new PdfPageBuilder();
        $page->addPageLink(50, 700, 100, 20, 0);

        $registry = new PdfObjectRegistry();
        // Provide a fake page reference for page index 0
        $fakePageRef = $registry->register(new PdfDictionary([]));
        $refs = $page->compileLinks($registry, [$fakePageRef]);

        self::assertCount(1, $refs);
        self::assertContainsOnlyInstancesOf(PdfIndirectReference::class, $refs);
    }
}
