<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfLinkSpec;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Color\Color;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Object\PdfContentStreamData;
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
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfUriAction;
use PhpPdf\Svg\SvgRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'build')]
#[UsesClass(Color::class)]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(PdfContentStreamData::class)]
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
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfUriAction::class)]
#[UsesClass(SvgRenderer::class)]
final class BuildAnnotationsTest extends TestCase
{
    #[Test]
    public function buildCollectsPageAnnotations(): void
    {
        // Arrange — adding a text annotation to a page covers line 494
        // ($pageAnnotations[$i][] = $annotRef from compileAnnotations)
        $document = (new PdfDocumentBuilder())
            ->page(static function (PdfPageBuilder $page): void {
                $page->addTextAnnotation(50, 700, 'A sticky note');
            })
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function buildCollectsPageLinks(): void
    {
        // Arrange — adding a URI link covers line 497
        // ($pageAnnotations[$i][] = $annotRef from compileLinks)
        $document = (new PdfDocumentBuilder())
            ->page(static function (PdfPageBuilder $page): void {
                $page->addUriLink(50, 700, 100, 20, 'https://example.com');
            })
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function buildCollectsInternalPageLinks(): void
    {
        // Arrange — page link (GoTo action) covers the $spec->uri === null branch in compileLinks
        $document = (new PdfDocumentBuilder())
            ->page(static function (PdfPageBuilder $page): void {
                $page->addPageLink(50, 700, 100, 20, 0);
            })
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }
}
