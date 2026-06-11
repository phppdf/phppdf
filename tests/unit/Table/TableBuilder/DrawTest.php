<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableBuilder;

use LogicException;
use PhpPdf\Color\Color;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\AppendLine;
use PhpPdf\Content\Operation\AppendRectangle;
use PhpPdf\Content\Operation\BeginSubpath;
use PhpPdf\Content\Operation\SetFont;
use PhpPdf\Content\Operation\SetLineWidth;
use PhpPdf\Content\Operation\SetNonStrokingGray;
use PhpPdf\Content\Operation\SetNonStrokingRgbColor;
use PhpPdf\Content\Operation\SetStrokingGray;
use PhpPdf\Content\Operation\SetTextMatrix;
use PhpPdf\Content\Operation\ShowText;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Table\TableBuilder;
use PhpPdf\Table\TableCell;
use PhpPdf\Table\TableRow;
use PhpPdf\Table\TableVerticalAlign;
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextAlign;
use PhpPdf\Text\TextBox;
use PhpPdf\Text\TextSpan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableBuilder::class)]
#[CoversMethod(TableBuilder::class, 'create')]
#[CoversMethod(TableBuilder::class, 'columns')]
#[CoversMethod(TableBuilder::class, 'padding')]
#[CoversMethod(TableBuilder::class, 'paddingAll')]
#[CoversMethod(TableBuilder::class, 'border')]
#[CoversMethod(TableBuilder::class, 'font')]
#[CoversMethod(TableBuilder::class, 'addRow')]
#[CoversMethod(TableBuilder::class, 'draw')]
#[UsesClass(TableCell::class)]
#[UsesClass(TableRow::class)]
#[UsesClass(TableVerticalAlign::class)]
#[UsesClass(TextAlign::class)]
#[UsesClass(RichTextBox::class)]
#[UsesClass(TextBox::class)]
#[UsesClass(TextSpan::class)]
#[UsesClass(Type1FontMetrics::class)]
#[UsesClass(Color::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(Matrix::class)]
#[UsesClass(AppendLine::class)]
#[UsesClass(AppendRectangle::class)]
#[UsesClass(BeginSubpath::class)]
#[UsesClass(SetFont::class)]
#[UsesClass(SetLineWidth::class)]
#[UsesClass(SetNonStrokingGray::class)]
#[UsesClass(SetNonStrokingRgbColor::class)]
#[UsesClass(SetStrokingGray::class)]
#[UsesClass(SetTextMatrix::class)]
#[UsesClass(ShowText::class)]
final class DrawTest extends TestCase
{
    private Type1FontMetrics $metrics;

    #[Test]
    public function createReturnsSelf(): void
    {
        $tb = TableBuilder::create(72, 700);

        self::assertInstanceOf(TableBuilder::class, $tb);
    }

    #[Test]
    public function columnsReturnsSelf(): void
    {
        $tb = TableBuilder::create(72, 700)->columns([100, 200]);

        self::assertInstanceOf(TableBuilder::class, $tb);
    }

    #[Test]
    public function paddingReturnsSelf(): void
    {
        $tb = TableBuilder::create(72, 700)->padding(5, 6, 4, 6);

        self::assertInstanceOf(TableBuilder::class, $tb);
    }

    #[Test]
    public function paddingAllReturnsSelf(): void
    {
        $tb = TableBuilder::create(72, 700)->paddingAll(8);

        self::assertInstanceOf(TableBuilder::class, $tb);
    }

    #[Test]
    public function borderReturnsSelf(): void
    {
        $tb = TableBuilder::create(72, 700)->border(Color::black(), 0.5);

        self::assertInstanceOf(TableBuilder::class, $tb);
    }

    #[Test]
    public function fontReturnsSelf(): void
    {
        $tb = TableBuilder::create(72, 700)->font('F1', 10, $this->metrics);

        self::assertInstanceOf(TableBuilder::class, $tb);
    }

    #[Test]
    public function addRowReturnsSelf(): void
    {
        $tb = TableBuilder::create(72, 700)
            ->addRow(TableRow::cells([TableCell::text('A')]));

        self::assertInstanceOf(TableBuilder::class, $tb);
    }

    // =========================================================================
    // draw() — error paths
    // =========================================================================

    #[Test]
    public function drawThrowsWhenFontNotSet(): void
    {
        $stream = new PdfContentStreamBuilder();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('font()');

        TableBuilder::create(72, 700)
            ->columns([100])
            ->addRow(TableRow::cells([TableCell::text('X')]))
            ->draw($stream);
    }

    #[Test]
    public function drawReturnsStartYWhenNoRows(): void
    {
        $stream = new PdfContentStreamBuilder();

        $bottomY = TableBuilder::create(72, 700)
            ->columns([100])
            ->font('F1', 10, $this->metrics)
            ->draw($stream);

        self::assertEqualsWithDelta(700.0, $bottomY, 0.001);
    }

    #[Test]
    public function drawReturnsStartYWhenNoColumns(): void
    {
        $stream = new PdfContentStreamBuilder();

        $bottomY = TableBuilder::create(72, 700)
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([TableCell::text('X')]))
            ->draw($stream);

        self::assertEqualsWithDelta(700.0, $bottomY, 0.001);
    }

    // =========================================================================
    // draw() — simple table
    // =========================================================================

    #[Test]
    public function drawSimpleOneRowTableReturnsBelowY(): void
    {
        // Arrange
        $stream = new PdfContentStreamBuilder();

        // Act
        $bottomY = TableBuilder::create(72, 700)
            ->columns([150, 150])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('Column A'),
                TableCell::text('Column B'),
            ]))
            ->draw($stream);

        // Assert — bottom is below starting y
        self::assertLessThan(700.0, $bottomY);
    }

    #[Test]
    public function drawWithCellBackgroundAndTextColor(): void
    {
        // Arrange — cell with background and text colour
        $stream = new PdfContentStreamBuilder();

        // Act / Assert (no exception)
        TableBuilder::create(72, 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('Styled')
                    ->background(Color::rgb(0.8, 0.8, 0.8))
                    ->textColor(Color::black()),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    #[Test]
    public function drawWithRowBackground(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(
                TableRow::cells([TableCell::text('Row')])
                    ->background(Color::rgb(0.9, 0.9, 0.9)),
            )
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — borders
    // =========================================================================

    #[Test]
    public function drawWithOuterAndInnerBorders(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->border(color: Color::black(), width: 0.5, outer: true, inner: true)
            ->addRow(TableRow::cells([
                TableCell::text('A'),
                TableCell::text('B'),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('C'),
                TableCell::text('D'),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    #[Test]
    public function drawWithOuterBorderOnly(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 0.5, outer: true, inner: false)
            ->addRow(TableRow::cells([TableCell::text('A'), TableCell::text('B')]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    #[Test]
    public function drawWithInnerBorderOnly(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 0.5, outer: false, inner: true)
            ->addRow(TableRow::cells([TableCell::text('A'), TableCell::text('B')]))
            ->addRow(TableRow::cells([TableCell::text('C'), TableCell::text('D')]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — colspan and rowspan
    // =========================================================================

    #[Test]
    public function drawWithColspan(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 100, 100])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 0.5)
            ->addRow(TableRow::cells([
                TableCell::text('Header spanning 2 cols')->colspan(2),
                TableCell::text('Right'),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('A'),
                TableCell::text('B'),
                TableCell::text('C'),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    #[Test]
    public function drawWithRowspan(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 200])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 0.5)
            ->addRow(TableRow::cells([
                TableCell::text('Tall')->rowspan(2),
                TableCell::text('Row 1 Right'),
            ]))
            ->addRow(TableRow::cells([
                // col 0 is occupied by rowspan above
                TableCell::text('Row 2 Right'),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — vertical alignment
    // =========================================================================

    #[Test]
    public function drawWithVerticalAlignMiddle(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('Middle')->verticalAlign(TableVerticalAlign::Middle),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    #[Test]
    public function drawWithVerticalAlignBottom(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('Bottom')->verticalAlign(TableVerticalAlign::Bottom),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — per-cell font override, padding override
    // =========================================================================

    #[Test]
    public function drawWithPerCellFontAndPadding(): void
    {
        $stream = new PdfContentStreamBuilder();
        $boldMetrics = Type1FontMetrics::helveticaBold();

        TableBuilder::create(72, 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('Bold cell')
                    ->font('F2', 12, $boldMetrics)
                    ->padding(10, 8, 8, 8),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — rowspan>1 height expansion (pass B)
    // =========================================================================

    #[Test]
    public function drawRowspanExpandsLastRowWhenNeeded(): void
    {
        // Arrange — tall cell spanning 2 rows forces the 2nd row to expand
        $stream = new PdfContentStreamBuilder();
        $longText = str_repeat('This is a very long text. ', 20);

        // Act / Assert
        $bottomY = TableBuilder::create(72, 700)
            ->columns([80, 200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text($longText)->rowspan(2),
                TableCell::text('Short'),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('Short too'),
            ]))
            ->draw($stream);

        self::assertLessThan(700.0, $bottomY);
    }

    // =========================================================================
    // draw() — border horizontal segment bridged by rowspan
    // =========================================================================

    #[Test]
    public function drawBorderSkipsHorizontalSegmentWhereRowspanBridges(): void
    {
        // The inner horizontal line between row 0 and 1 is partially omitted
        // where the rowspan=2 cell bridges the edge.
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 0.5)
            ->addRow(TableRow::cells([
                TableCell::text('Rowspan')->rowspan(2),
                TableCell::text('Row 0 B'),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('Row 1 B'),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — border vertical segment bridged by colspan
    // =========================================================================

    #[Test]
    public function drawBorderSkipsVerticalSegmentWhereColspanBridges(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 0.5)
            ->addRow(TableRow::cells([
                TableCell::text('Spanning')->colspan(2),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('A'),
                TableCell::text('B'),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — empty cells are generated when row has fewer cells than columns
    // =========================================================================

    #[Test]
    public function drawFillsMissingCellsWithEmptyText(): void
    {
        $stream = new PdfContentStreamBuilder();

        // Only 1 cell provided for a 3-column table
        TableBuilder::create(72, 700)
            ->columns([100, 100, 100])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([TableCell::text('Only one')]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — custom stroke width
    // =========================================================================

    #[Test]
    public function drawWithCustomBorderWidth(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([150])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 2.0)
            ->addRow(TableRow::cells([TableCell::text('Thick border')]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — horizontal segment emitted mid-loop when bridged column interrupts
    //   Covers the branch: $segStart !== null when a bridged column is hit
    //   (lines inside the horizontal inner-border loop).
    // =========================================================================

    #[Test]
    public function drawHorizontalBorderEmitsSegmentWhenBridgedColumnInterruptsSequence(): void
    {
        // Layout: 3 columns — col 1 has rowspan=2 bridging the only row boundary.
        //   Row 0: [A] [B(rowspan=2)] [C]
        //   Row 1: [D] (B occupies col 1)  [E]
        //
        // At the row-0/row-1 boundary, the scan proceeds:
        //   col 0: not bridged → segStart = colX[0]
        //   col 1: bridged     → segStart != null → emit [col0..col1], reset
        //   col 2: not bridged → segStart = colX[2]
        // End of loop: emit [col2..end].
        // Both emit paths (mid-loop and end-of-loop) are exercised.
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 100, 100])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 0.5)
            ->addRow(TableRow::cells([
                TableCell::text('A'),
                TableCell::text('B')->rowspan(2),
                TableCell::text('C'),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('D'),
                // col 1 is occupied by B's rowspan
                TableCell::text('E'),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — vertical segment emitted mid-loop when bridged row interrupts
    //   Covers the branch: $segTop !== null when a bridged row is hit
    //   (lines inside the vertical inner-border loop).
    // =========================================================================

    #[Test]
    public function drawVerticalBorderEmitsSegmentWhenBridgedRowInterruptsSequence(): void
    {
        // Layout: 3 rows, 2 columns — row 1 has colspan=2 bridging col 0/1.
        //   Row 0: [A] [B]
        //   Row 1: [C(colspan=2)]
        //   Row 2: [D] [E]
        //
        // At the col-0/col-1 inner vertical line, the scan proceeds:
        //   row 0: not bridged → segTop = rowY[0]
        //   row 1: bridged     → segTop != null → emit [row0..row1], reset
        //   row 2: not bridged → segTop = rowY[2]
        // End of loop: emit [row2..tableBottom].
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 0.5)
            ->addRow(TableRow::cells([
                TableCell::text('A'),
                TableCell::text('B'),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('C')->colspan(2),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('D'),
                TableCell::text('E'),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — vertical inner border fully bridged by colspan in every row
    //   Covers the `if ($segTop === null) { continue; }` branch (line 349):
    //   every row bridges the col0/col1 boundary, so $segTop never gets set.
    // =========================================================================

    #[Test]
    public function drawVerticalBorderSkipsColumnFullyBridgedInEveryRow(): void
    {
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 0.5)
            ->addRow(TableRow::cells([
                TableCell::text('Spanning row 0')->colspan(2),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('Spanning row 1')->colspan(2),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — row where all columns are occupied by rowspan cells from above
    //   Covers the break when ci >= colCount after advancing past occupied positions.
    // =========================================================================

    #[Test]
    public function drawRowWithAllColumnsOccupiedByRowspanBreaksGridLoop(): void
    {
        // Both cells in row 0 span 2 rows, making row 1 entirely occupied.
        // When the layout engine processes row 1 it advances ci past colCount
        // and hits the safety break.
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->border(Color::black(), 0.5)
            ->addRow(TableRow::cells([
                TableCell::text('A')->rowspan(2),
                TableCell::text('B')->rowspan(2),
            ]))
            ->addRow(TableRow::cells([]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    // =========================================================================
    // draw() — precise return value
    // =========================================================================

    #[Test]
    public function drawReturnsPreciseTableBottomForSingleLineRow(): void
    {
        // Arrange
        $stream = new PdfContentStreamBuilder();

        // Act
        $bottomY = TableBuilder::create(x: 72, y: 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([TableCell::text('Single line')]))
            ->draw($stream);

        // Assert — rowHeight = pt + pb = (5 + 10×0.72) + (4 + 10×0.20) = 12.2 + 6.0 = 18.2
        self::assertEqualsWithDelta(681.8, $bottomY, 0.001);
    }

    // =========================================================================
    // draw() — rich-text (span) cells
    // =========================================================================

    #[Test]
    public function drawWithRichTextSpansCell(): void
    {
        // Arrange
        $stream = new PdfContentStreamBuilder();

        // Act / Assert (no exception thrown; RichTextBox path exercised)
        TableBuilder::create(72, 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::spans([
                    TextSpan::create('Hello ', 'F1', 10, $this->metrics),
                    TextSpan::create('World', 'F2', 12, Type1FontMetrics::helveticaBold()),
                ]),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    #[Test]
    public function drawWithRichTextSpansCellAndTextColor(): void
    {
        // Arrange — span cell with a text colour override exercises the graphicsState
        // save/restore path in the text-rendering phase
        $stream = new PdfContentStreamBuilder();

        TableBuilder::create(72, 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::spans([
                    TextSpan::create('Colored span', 'F1', 10, $this->metrics),
                ])->textColor(Color::black()),
            ]))
            ->draw($stream);

        self::addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        $this->metrics = Type1FontMetrics::helvetica();
    }
}
