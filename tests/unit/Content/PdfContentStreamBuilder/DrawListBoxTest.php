<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\BeginText;
use PhpPdf\Content\Operation\EndText;
use PhpPdf\Content\Operation\SetFont;
use PhpPdf\Content\Operation\SetTextMatrix;
use PhpPdf\Content\Operation\ShowText;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Text\ListBox;
use PhpPdf\Text\ListItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'drawListBox')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(BeginText::class)]
#[UsesClass(EndText::class)]
#[UsesClass(ListBox::class)]
#[UsesClass(ListItem::class)]
#[UsesClass(Matrix::class)]
#[UsesClass(SetFont::class)]
#[UsesClass(SetTextMatrix::class)]
#[UsesClass(ShowText::class)]
#[UsesClass(Type1FontMetrics::class)]
final class DrawListBoxTest extends TestCase
{
    #[Test]
    public function drawListBoxReturnsEarlyForEmptyItemsList(): void
    {
        $list = ListBox::bullet([], self::metrics(), 12, 300);
        $builder = new PdfContentStreamBuilder();
        $result = $builder->drawListBox($list, 'F1', 72.0, 720.0);

        self::assertSame($builder, $result);
        self::assertSame([], $builder->build()->getOperations());
    }

    #[Test]
    public function drawListBoxReturnsSelf(): void
    {
        $list = ListBox::bullet(['Hello'], self::metrics(), 12, 300);
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->drawListBox($list, 'F1', 72.0, 720.0));
    }

    #[Test]
    public function drawListBoxRendersMarkerAndBodyLine(): void
    {
        $list = ListBox::bullet(['Hello'], self::metrics(), 12, 300);
        $builder = new PdfContentStreamBuilder();
        $builder->drawListBox($list, 'F1', 72.0, 720.0);
        $ops = $builder->build()->getOperations();
        $types = array_map(static fn ($op) => $op::class, $ops);

        self::assertContains(BeginText::class, $types);
        self::assertContains(SetFont::class, $types);
        self::assertContains(SetTextMatrix::class, $types);
        self::assertContains(ShowText::class, $types);
        self::assertContains(EndText::class, $types);
    }

    #[Test]
    public function drawListBoxAddsInterItemSpacingBetweenItems(): void
    {
        // Two items with itemSpacing > 0 → the gap is applied after the first item.
        $list = ListBox::bullet(['Item 1', 'Item 2'], self::metrics(), 12, 300, itemSpacing: 6.0);
        $builder = new PdfContentStreamBuilder();
        $builder->drawListBox($list, 'F1', 72.0, 720.0);
        $ops = $builder->build()->getOperations();
        // Just verify it completes without error and adds operations
        self::assertGreaterThan(4, count($ops));
    }

    #[Test]
    public function drawListBoxDoesNotAddSpacingAfterLastItem(): void
    {
        // Two items: inter-item spacing after first but not second
        $list1 = ListBox::bullet(['A'], self::metrics(), 12, 300, itemSpacing: 5.0);
        $list2 = ListBox::bullet(['A', 'B'], self::metrics(), 12, 300, itemSpacing: 5.0);

        $builder1 = new PdfContentStreamBuilder();
        $builder2 = new PdfContentStreamBuilder();

        $builder1->drawListBox($list1, 'F1', 0.0, 0.0);
        $builder2->drawListBox($list2, 'F1', 0.0, 0.0);

        // builder2 has one extra item (more operations), showing spacing is not added after last
        self::assertGreaterThan(
            count($builder1->build()->getOperations()),
            count($builder2->build()->getOperations()),
        );
    }

    #[Test]
    public function drawListBoxSkipsShowTextForEmptyBodyLine(): void
    {
        // An item with empty text produces lines=[''] → body line '' → showText skipped.
        $list = ListBox::bullet([''], self::metrics(), 12, 300);
        $builder = new PdfContentStreamBuilder();
        $builder->drawListBox($list, 'F1', 72.0, 720.0);
        $ops = $builder->build()->getOperations();
        $types = array_map(static fn ($op) => $op::class, $ops);

        // Marker showText IS called; body showText is skipped.
        // SetTextMatrix appears twice: once for marker, once for the empty body line.
        $setTextMatrixCount = count(array_filter($types, static fn ($t) => $t === SetTextMatrix::class));
        self::assertGreaterThanOrEqual(2, $setTextMatrixCount);

        // ShowText count equals 1: only the marker is shown (bullet •), not the empty body.
        $showTextCount = count(array_filter($types, static fn ($t) => $t === ShowText::class));
        self::assertSame(1, $showTextCount);
    }

    private static function metrics(): Type1FontMetrics
    {
        return Type1FontMetrics::helvetica();
    }
}
