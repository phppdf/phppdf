<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfOutlineBuilder;
use PhpPdf\Builder\PdfOutlineItemSpec;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfDestination;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfOutline;
use PhpPdf\Object\PdfOutlineItem;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Svg\SvgRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'outline')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(PdfContentStreamData::class)]
#[UsesClass(PdfDestination::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfOutline::class)]
#[UsesClass(PdfOutlineBuilder::class)]
#[UsesClass(PdfOutlineItem::class)]
#[UsesClass(PdfOutlineItemSpec::class)]
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(SvgRenderer::class)]
final class OutlineTest extends TestCase
{
    #[Test]
    public function outlineReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->outline(static fn () => null);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function outlineWithNoItemsSkipsBuildingOutline(): void
    {
        // Arrange — outline configured with no items; buildOutline should return early
        $document = (new PdfDocumentBuilder())
            ->outline(static fn (PdfOutlineBuilder $o) => null)
            ->page(static fn () => null)
            ->build();

        // Assert — document still builds successfully
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function outlineWithNoItemsAndNoPagesSkipsBuildingOutline(): void
    {
        // Arrange — outline configured but no pages exist; the && count($pageRefs) > 0
        // condition prevents buildOutline from being called at all.
        $document = (new PdfDocumentBuilder())
            ->outline(static function (PdfOutlineBuilder $o): void {
                $o->item('Intro', 0);
            })
            ->build();

        // Assert — document still builds successfully
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function outlineWithSingleItemBuildsOutline(): void
    {
        // Arrange / Act
        $document = (new PdfDocumentBuilder())
            ->outline(static function (PdfOutlineBuilder $o): void {
                $o->item('Intro', 0);
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function outlineWithMultipleItemsLinksNextAndPrev(): void
    {
        // Arrange / Act — three items cover the Prev/Next sibling-link code paths
        $document = (new PdfDocumentBuilder())
            ->outline(static function (PdfOutlineBuilder $o): void {
                $o->item('Chapter 1', 0);
                $o->item('Chapter 2', 1);
                $o->item('Chapter 3', 2);
            })
            ->page(static fn () => null)
            ->page(static fn () => null)
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function outlineWithNestedItemsBuildsRecursively(): void
    {
        // Arrange / Act — nested items cover the recursive buildOutlineLevel path
        $document = (new PdfDocumentBuilder())
            ->outline(static function (PdfOutlineBuilder $o): void {
                $o->item('Part I', 0, static function (PdfOutlineBuilder $children): void {
                    $children->item('Section 1.1', 0);
                    $children->item('Section 1.2', 1);
                });
                $o->item('Part II', 2);
            })
            ->page(static fn () => null)
            ->page(static fn () => null)
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }
}
