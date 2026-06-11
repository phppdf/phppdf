<?php

declare(strict_types=1);

namespace PhpPdf\Table\TableBuilder;

use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Table\TableBuilder;
use PhpPdf\Table\TableCell;
use PhpPdf\Table\TableRow;
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextBox;
use PhpPdf\Text\TextSpan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * @phpstan-import-type Layout from \PhpPdf\Table\TableBuilder
 */
#[CoversClass(TableBuilder::class)]
#[CoversMethod(TableBuilder::class, 'computeLayout')]
#[UsesClass(TableCell::class)]
#[UsesClass(TableRow::class)]
#[UsesClass(TextBox::class)]
#[UsesClass(RichTextBox::class)]
#[UsesClass(TextSpan::class)]
#[UsesClass(Type1FontMetrics::class)]
final class ComputeLayoutTest extends TestCase
{
    private Type1FontMetrics $metrics;

    #[Test]
    public function columnXPositionsStartAtTableXAndAccumulateWidths(): void
    {
        // Arrange
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([100, 200, 50])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('A'),
                TableCell::text('B'),
                TableCell::text('C'),
            ]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        // Assert
        self::assertEqualsWithDelta(72.0, $layout['colX'][0], 0.001);
        self::assertEqualsWithDelta(172.0, $layout['colX'][1], 0.001); // 72 + 100
        self::assertEqualsWithDelta(372.0, $layout['colX'][2], 0.001); // 72 + 100 + 200
    }

    // =========================================================================
    // Row y-positions and table bottom
    // =========================================================================

    #[Test]
    public function rowYPositionsStartAtTableYAndDecrementByRowHeight(): void
    {
        // Arrange — single-line cells; rowHeight = pt + pb = (5 + 10×0.72) + (4 + 10×0.20) = 18.2
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([TableCell::text('Row 0')]))
            ->addRow(TableRow::cells([TableCell::text('Row 1')]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        // Assert
        self::assertEqualsWithDelta(700.0, $layout['rowY'][0], 0.001);
        self::assertEqualsWithDelta(700.0 - 18.2, $layout['rowY'][1], 0.001);
    }

    #[Test]
    public function tableBottomIsYMinusSumOfAllRowHeights(): void
    {
        // Arrange — two single-line rows of 18.2 pt each
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([TableCell::text('Row 0')]))
            ->addRow(TableRow::cells([TableCell::text('Row 1')]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        // Assert — tableBottom = 700 − 2 × 18.2 = 663.6
        self::assertEqualsWithDelta(663.6, $layout['tableBottom'], 0.001);
    }

    // =========================================================================
    // Row heights
    // =========================================================================

    #[Test]
    public function singleLineRowHeightEqualsDefaultPaddingOffsetsOnly(): void
    {
        // Arrange
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([TableCell::text('One line')]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        // Assert — contentHeight = 0 for a single wrapped line, so height = pt + pb
        // pt = 5 + 10×0.72 = 12.2; pb = 4 + 10×0.20 = 6.0; total = 18.2
        self::assertEqualsWithDelta(18.2, $layout['rowHeights'][0], 0.001);
    }

    // =========================================================================
    // Colspan
    // =========================================================================

    #[Test]
    public function colspanPlacementWidthSumsSpannedColumnWidths(): void
    {
        // Arrange — cell spans both columns (100 + 200 = 300)
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([100, 200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('Spanning')->colspan(2),
            ]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        // Assert
        self::assertEqualsWithDelta(300.0, $layout['placements'][0]['width'], 0.001);
    }

    #[Test]
    public function colspanPlacementXPositionIsAtFirstSpannedColumn(): void
    {
        // Arrange — second cell spans columns 1 and 2 (width 100 + 200 = 300)
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([60, 100, 200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('A'),
                TableCell::text('B')->colspan(2),
            ]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        $spanPlacement = $layout['placements'][1];

        // Assert — spanning cell starts at colX[1] = 72 + 60 = 132
        self::assertEqualsWithDelta(132.0, $spanPlacement['x'], 0.001);
        self::assertEqualsWithDelta(300.0, $spanPlacement['width'], 0.001);
    }

    // =========================================================================
    // Rowspan — occupiedBy
    // =========================================================================

    #[Test]
    public function rowspanOccupiedByMapsAllCoveredGridPositions(): void
    {
        // Arrange — top-left cell spans 2 rows
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('Tall')->rowspan(2),
                TableCell::text('R0C1'),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('R1C1'),
            ]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        // Assert — (1,0) must point back to the originating cell at (0,0)
        self::assertSame(0, $layout['occupiedBy'][0][0]['rowStart']);
        self::assertSame(0, $layout['occupiedBy'][0][0]['colStart']);
        self::assertSame(0, $layout['occupiedBy'][1][0]['rowStart']);
        self::assertSame(0, $layout['occupiedBy'][1][0]['colStart']);
        // (1,1) belongs to its own cell
        self::assertSame(1, $layout['occupiedBy'][1][1]['rowStart']);
        self::assertSame(1, $layout['occupiedBy'][1][1]['colStart']);
    }

    // =========================================================================
    // Rowspan — placement height and y-position
    // =========================================================================

    #[Test]
    public function rowspanPlacementHeightSumsAllSpannedRowHeights(): void
    {
        // Arrange — top-left cell spans 2 rows of 18.2 pt each
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('Tall')->rowspan(2),
                TableCell::text('R0C1'),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('R1C1'),
            ]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        // Assert — height = rowHeights[0] + rowHeights[1] = 18.2 + 18.2 = 36.4
        $spanPlacement = $layout['placements'][0];
        self::assertEqualsWithDelta(36.4, $spanPlacement['height'], 0.001);
        self::assertEqualsWithDelta(700.0, $spanPlacement['y'], 0.001);
    }

    // =========================================================================
    // Rowspan — pass B height expansion
    // =========================================================================

    #[Test]
    public function rowspanPassBExpandsLastSpannedRowWhenContentExceedsSpanHeight(): void
    {
        // Arrange — narrow column forces many-line wrapping; the rowspan cell ends
        // up taller than the two single-line adjacent rows combined (2 × 18.2 = 36.4).
        $tallText = str_repeat('text ', 30);

        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([50, 200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text($tallText)->rowspan(2),
                TableCell::text('Short'),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('Short too'),
            ]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        // Assert — row 1 must have grown beyond its initial pass-A height of 18.2
        self::assertGreaterThan(18.2, $layout['rowHeights'][1]);
    }

    // =========================================================================
    // Placements count
    // =========================================================================

    #[Test]
    public function placementsContainOneEntryPerLogicalCell(): void
    {
        // Arrange — 2 rows × 2 cols = 4 logical cells
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([TableCell::text('A'), TableCell::text('B')]))
            ->addRow(TableRow::cells([TableCell::text('C'), TableCell::text('D')]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        // Assert
        self::assertCount(4, $layout['placements']);
    }

    #[Test]
    public function rowspanDoesNotAddExtraPlacementForOccupiedPositions(): void
    {
        // Arrange — rowspan=2 in col 0 means (1,0) is occupied but not its own cell
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([100, 100])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('Tall')->rowspan(2),
                TableCell::text('R0C1'),
            ]))
            ->addRow(TableRow::cells([
                TableCell::text('R1C1'),
            ]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        // Assert — 3 logical cells: (0,0) with rowspan, (0,1), (1,1)
        self::assertCount(3, $layout['placements']);
    }

    // =========================================================================
    // Per-cell padding override
    // =========================================================================

    #[Test]
    public function perCellPaddingOverrideAffectsPlacementPtPbPlPr(): void
    {
        // Arrange — cell overrides all four padding values
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::text('Padded')->padding(10.0, 8.0, 8.0, 8.0),
            ]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        $p = $layout['placements'][0];

        // Assert — pt = paddingTop + fontSize×0.72 = 10 + 7.2 = 17.2
        //           pb = paddingBottom + fontSize×0.20 = 8 + 2.0 = 10.0
        self::assertEqualsWithDelta(17.2, $p['pt'], 0.001);
        self::assertEqualsWithDelta(10.0, $p['pb'], 0.001);
        self::assertEqualsWithDelta(8.0, $p['pl'], 0.001);
        self::assertEqualsWithDelta(8.0, $p['pr'], 0.001);
    }

    // =========================================================================
    // Rich-text (spans) cells
    // =========================================================================

    #[Test]
    public function richTextCellUsesMaxSpanFontSizeForPaddingOffsets(): void
    {
        // Arrange — spans with sizes 10 and 12; the larger (12) drives pt/pb
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::spans([
                    TextSpan::create('Normal ', 'F1', 10, $this->metrics),
                    TextSpan::create('Larger', 'F2', 12, Type1FontMetrics::helveticaBold()),
                ]),
            ]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        $p = $layout['placements'][0];

        // Assert — pt = 5 + 12×0.72 = 13.64; pb = 4 + 12×0.20 = 6.4
        self::assertEqualsWithDelta(13.64, $p['pt'], 0.001);
        self::assertEqualsWithDelta(6.4, $p['pb'], 0.001);
    }

    #[Test]
    public function richTextCellFallsBackToDefaultFontSizeWhenAllSpanSizesAreZero(): void
    {
        // Arrange — spans with fontSize 0 must fall back to the builder default (10)
        $builder = TableBuilder::create(x: 72, y: 700)
            ->columns([200])
            ->font('F1', 10, $this->metrics)
            ->addRow(TableRow::cells([
                TableCell::spans([
                    TextSpan::create('text', 'F1', 0.0, $this->metrics),
                ]),
            ]));

        // Act
        $layout = $this->invokeComputeLayout($builder);

        $p = $layout['placements'][0];

        // Assert — fontSize falls back to defaultFontSize = 10
        // pt = 5 + 10×0.72 = 12.2; pb = 4 + 10×0.20 = 6.0
        self::assertEqualsWithDelta(12.2, $p['pt'], 0.001);
        self::assertEqualsWithDelta(6.0, $p['pb'], 0.001);
    }

    protected function setUp(): void
    {
        $this->metrics = Type1FontMetrics::helvetica();
    }

    /**
     * @return Layout
     */
    private function invokeComputeLayout(TableBuilder $builder): array
    {
        /** @var Layout $layout */
        $layout = (new ReflectionMethod(TableBuilder::class, 'computeLayout'))->invoke($builder);

        return $layout;
    }
}
