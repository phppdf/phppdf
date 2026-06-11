<?php

declare(strict_types=1);

namespace PhpPdf\Table;

use LogicException;
use PhpPdf\Color\Color;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextBox;

/**
 * @phpstan-type OccupiedCell array{rowStart: int, colStart: int}
 * @phpstan-type GridCell array{cell: TableCell, colspan: int<1, max>, rowBg: Color|null, rowspan: int<1, max>}
 * @phpstan-type PlacementData array{
 *     cell: TableCell,
 *     colspan: int<1, max>,
 *     rowBg: Color|null,
 *     rowspan: int<1, max>,
 *     fontName: string,
 *     pb: float,
 *     pl: float,
 *     pr: float,
 *     pt: float,
 *     spanWidth: float,
 *     textBox: TextBox|RichTextBox,
 * }
 * @phpstan-type Placement array{
 *     cell: TableCell,
 *     colspan: int<1, max>,
 *     colStart: int,
 *     fontName: string,
 *     height: float,
 *     pb: float,
 *     pl: float,
 *     pr: float,
 *     pt: float,
 *     rowBg: Color|null,
 *     rowspan: int<1, max>,
 *     rowStart: int,
 *     textBox: TextBox|RichTextBox,
 *     width: float,
 *     x: float,
 *     y: float,
 * }
 * @phpstan-type Layout array{
 *     tableBottom: float,
 *     rowHeights: array<int, float>,
 *     rowY: array<int, float>,
 *     colX: array<int, float>,
 *     placements: list<Placement>,
 *     occupiedBy: array<int, array<int, OccupiedCell>>,
 * }
 */
final class TableBuilder
{
    /** @var array<float> Column widths in points, indexed by column position. */
    private array $columnWidths = [];

    /** Default top padding in points (visual gap from cell top to cap-height). */
    private float $defaultPaddingTop = 5.0;

    /** Default right padding in points. */
    private float $defaultPaddingRight = 6.0;

    /** Default bottom padding in points (visual gap from descender to cell bottom). */
    private float $defaultPaddingBottom = 4.0;

    /** Default left padding in points. */
    private float $defaultPaddingLeft = 6.0;

    /** Border colour; null means no borders are drawn. */
    private ?Color $borderColor = null;

    /** Width of all border lines in points. */
    private float $borderWidth = 0.5;

    /** Whether to draw a rectangle around the entire table. */
    private bool $outerBorder = true;

    /** Whether to draw lines between rows and between columns. */
    private bool $innerBorder = true;

    /** PDF resource name of the default font. */
    private string $defaultFontName = '';

    /** Default font size in points. */
    private float $defaultFontSize = 10.0;

    /** Metrics for the default font; null until font() is called. */
    private ?FontMetrics $defaultMetrics = null;

    /** @var list<\PhpPdf\Table\TableRow> */
    private array $rows = [];

    /** Use TableBuilder::create() to construct instances. */
    private function __construct(private float $x, private float $y,)
    {
    }

    /**
     * Creates a TableBuilder whose top-left corner is at ($x, $y).
     *
     * $y is the PDF y coordinate of the TOP edge of the table (larger value,
     * since PDF y increases upward from the page bottom).
     */
    public static function create(float $x, float $y): self
    {
        return new self($x, $y);
    }

    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

    /**
     * Sets the width of each column in points.
     *
     * The number of elements determines the number of columns. Extra cells in
     * a row beyond this count are ignored; missing cells render as empty.
     *
     * @param array<float> $widths
     */
    public function columns(array $widths): self
    {
        $this->columnWidths = array_values($widths);

        return $this;
    }

    /**
     * Sets the default cell padding (top, right, bottom, left) in points.
     *
     * $top — visual gap from the cell's top edge to the top of capital letters.
     * $bottom — visual gap from the bottom of descenders to the cell's bottom edge.
     * $left / $right — gap from the cell edge to the start/end of text lines.
     *
     * The layout engine converts $top and $bottom to baseline offsets internally
     * by adding the font's approximate cap-height (fontSize × 0.72) and
     * descender depth (fontSize × 0.20), so the values you pass here represent
     * the actual white-space the reader will see.
     */
    public function padding(float $top, float $right, float $bottom, float $left): self
    {
        $this->defaultPaddingTop = $top;
        $this->defaultPaddingRight = $right;
        $this->defaultPaddingBottom = $bottom;
        $this->defaultPaddingLeft = $left;

        return $this;
    }

    /** Sets uniform padding on all four sides. */
    public function paddingAll(float $all): self
    {
        return $this->padding($all, $all, $all, $all);
    }

    /**
     * Enables cell borders.
     *
     * $outer — draw a rectangle around the entire table.
     * $inner — draw lines between rows and between columns; lines are omitted
     *          where a colspan or rowspan cell crosses the grid edge.
     */
    public function border(Color $color, float $width = 0.5, bool $outer = true, bool $inner = true): self
    {
        $this->borderColor = $color;
        $this->borderWidth = $width;
        $this->outerBorder = $outer;
        $this->innerBorder = $inner;

        return $this;
    }

    /**
     * Sets the default font used for all cells.
     *
     * $name must match a font registered with PdfPageBuilder::useType1Font() or
     * useEmbeddedFont() on the same page. Individual cells can override this
     * with TableCell::font().
     *
     * Must be called before draw().
     */
    public function font(string $name, float $size, FontMetrics $metrics): self
    {
        $this->defaultFontName = $name;
        $this->defaultFontSize = $size;
        $this->defaultMetrics = $metrics;

        return $this;
    }

    /** Appends a row to the table. */
    public function addRow(TableRow $row): self
    {
        $this->rows[] = $row;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    /**
     * Renders the table into $stream and returns the y coordinate immediately
     * below the bottom edge of the table.
     *
     * @throws \LogicException if font() has not been called.
     */
    public function draw(PdfContentStreamBuilder $stream): float
    {
        if ($this->defaultMetrics === null) {
            throw new LogicException('TableBuilder::font() must be called before draw().');
        }

        if ($this->rows === [] || $this->columnWidths === []) {
            return $this->y;
        }

        $layout = $this->computeLayout();
        $colCount = count($this->columnWidths);
        $rowCount = count($this->rows);

        // ---- Phase 2: cell backgrounds ----------------------------------------

        foreach ($layout['placements'] as $p) {
            $bg = $p['cell']->getBackground() ?? $p['rowBg'];

            if ($bg === null) {
                continue;
            }

            $stream
                ->saveGraphicsState()
                ->fillColor($bg)
                ->rectangle($p['x'], $p['y'] - $p['height'], $p['width'], $p['height'])
                ->fill()
                ->restoreGraphicsState();
        }

        // ---- Phase 3: text ----------------------------------------------------

        foreach ($layout['placements'] as $p) {
            $textColor = $p['cell']->getTextColor();

            if ($textColor !== null) {
                $stream->saveGraphicsState()->fillColor($textColor);
            }

            if ($p['textBox'] instanceof RichTextBox) {
                $stream->drawRichTextBox(
                    $p['textBox'],
                    $p['x'] + $p['pl'],
                    $this->textBaselineY($p),
                );
            } else {
                $stream->drawTextBox(
                    $p['textBox'],
                    $p['fontName'],
                    $p['x'] + $p['pl'],
                    $this->textBaselineY($p),
                );
            }

            if ($textColor === null) {
                continue;
            }

            $stream->restoreGraphicsState();
        }

        // ---- Phase 4: borders -------------------------------------------------

        if ($this->borderColor !== null && ($this->outerBorder || $this->innerBorder)) {
            $tableWidth = (float) array_sum($this->columnWidths);
            $tableBottom = $layout['tableBottom'];
            $tableHeight = $this->y - $tableBottom;
            $rowY = $layout['rowY'];
            $rowHeights = $layout['rowHeights'];
            $colX = $layout['colX'];
            $occupiedBy = $layout['occupiedBy'];

            $stream
                ->saveGraphicsState()
                ->strokeColor($this->borderColor)
                ->setLineWidth($this->borderWidth);

            if ($this->outerBorder) {
                $stream->rectangle($this->x, $tableBottom, $tableWidth, $tableHeight);
            }

            if ($this->innerBorder) {
                // Inner horizontal lines (between row r and r+1).
                // A segment is omitted where a rowspan cell bridges the edge.
                for ($r = 0; $r < $rowCount - 1; $r++) {
                    $lineY = $rowY[$r] - $rowHeights[$r];
                    $segStart = null;

                    for ($c = 0; $c < $colCount; $c++) {
                        $ownAbove = $occupiedBy[$r][$c] ?? ['rowStart' => $r, 'colStart' => $c];
                        $ownBelow = $occupiedBy[$r + 1][$c] ?? ['rowStart' => $r + 1, 'colStart' => $c];

                        $bridged = $ownAbove['rowStart'] === $ownBelow['rowStart']
                                && $ownAbove['colStart'] === $ownBelow['colStart'];

                        if (!$bridged) {
                            if ($segStart === null) {
                                $segStart = $colX[$c];
                            }
                        } else {
                            if ($segStart !== null) {
                                $stream->moveTo($segStart, $lineY)->lineTo($colX[$c], $lineY);
                                $segStart = null;
                            }
                        }
                    }

                    if ($segStart === null) {
                        continue;
                    }

                    $stream->moveTo($segStart, $lineY)->lineTo($this->x + $tableWidth, $lineY);
                }

                // Inner vertical lines (between column c and c+1).
                // A segment is omitted where a colspan cell bridges the edge.
                for ($c = 0; $c < $colCount - 1; $c++) {
                    $lineX = $colX[$c] + $this->columnWidths[$c];
                    $segTop = null;

                    for ($r = 0; $r < $rowCount; $r++) {
                        $ownLeft = $occupiedBy[$r][$c] ?? ['rowStart' => $r, 'colStart' => $c];
                        $ownRight = $occupiedBy[$r][$c + 1] ?? ['rowStart' => $r, 'colStart' => $c + 1];

                        $bridged = $ownLeft['rowStart'] === $ownRight['rowStart']
                                && $ownLeft['colStart'] === $ownRight['colStart'];

                        if (!$bridged) {
                            if ($segTop === null) {
                                $segTop = $rowY[$r];
                            }
                        } else {
                            if ($segTop !== null) {
                                $stream->moveTo($lineX, $segTop)->lineTo($lineX, $rowY[$r]);
                                $segTop = null;
                            }
                        }
                    }

                    if ($segTop === null) {
                        continue;
                    }

                    $stream->moveTo($lineX, $segTop)->lineTo($lineX, $tableBottom);
                }
            }

            $stream->stroke()->restoreGraphicsState();
        }

        return $layout['tableBottom'];
    }

    // -------------------------------------------------------------------------
    // Layout
    // -------------------------------------------------------------------------

    /**
     * Computes the full layout in four phases:
     *   1. Grid assignment — places each cell at its (row, col) origin,
     *      tracking every grid position in $occupiedBy.
     *   2. TextBox creation — builds a TextBox for each cell using the
     *      combined width of all spanned columns.
     *   3. Row heights — first from rowspan=1 cells, then expanded for
     *      rowspan>1 cells by growing the last row of each span.
     *   4. Absolute positions — converts row/column indices to x/y coordinates
     *      and builds a flat list of placements for the drawing phases.
     *
     * @return Layout
     */
    private function computeLayout(): array
    {
        if ($this->defaultMetrics === null) {
            throw new LogicException('TableBuilder::font() must be called before draw().');
        }

        $colCount = count($this->columnWidths);
        $rowCount = count($this->rows);

        // ---- Phase 1: grid assignment ----------------------------------------
        //
        // $occupiedBy[ri][ci] = ['rowStart' => int, 'colStart' => int]
        //   Points every grid position to the cell that owns it. Needed to
        //   decide which border segments to skip.
        //
        // $placementData[ri][ci] = raw cell data for cells that START at (ri,ci).

        /** @var array<int, array<int, OccupiedCell>> $occupiedBy */
        $occupiedBy = [];

        /** @var array<int, array<int, GridCell>> $gridCells */
        $gridCells = [];

        foreach ($this->rows as $ri => $row) {
            $cells = $row->getCells();
            $ci = 0;
            $cellIdx = 0;

            while ($ci < $colCount) {
                // Advance past columns occupied by rowspan cells from above.
                while ($ci < $colCount && isset($occupiedBy[$ri][$ci])) {
                    $ci++;
                }

                if ($ci >= $colCount) {
                    break;
                }

                $cell = $cells[$cellIdx] ?? TableCell::text('');
                $colspan = max(1, min($cell->getColspan(), $colCount - $ci));
                $rowspan = max(1, min($cell->getRowspan(), $rowCount - $ri));

                // Mark every grid position covered by this cell.
                for ($r2 = $ri; $r2 < $ri + $rowspan; $r2++) {
                    for ($c2 = $ci; $c2 < $ci + $colspan; $c2++) {
                        $occupiedBy[$r2][$c2] = ['rowStart' => $ri, 'colStart' => $ci];
                    }
                }

                $gridCells[$ri][$ci] = [
                    'cell' => $cell,
                    'colspan' => $colspan,
                    'rowBg' => $row->getBackground(),
                    'rowspan' => $rowspan,
                ];

                $ci += $colspan;
                $cellIdx += 1;
            }
        }

        // ---- Phase 2: TextBox creation ---------------------------------------

        /** @var array<int, array<int, PlacementData>> $placementData */
        $placementData = [];

        foreach ($gridCells as $ri => $rowCells) {
            foreach ($rowCells as $ci => $data) {
                $cell = $data['cell'];
                $colspan = $data['colspan'];

                $pr = $cell->getPaddingRight() ?? $this->defaultPaddingRight;
                $pl = $cell->getPaddingLeft() ?? $this->defaultPaddingLeft;

                $spanWidth = 0.0;

                for ($c2 = $ci; $c2 < $ci + $colspan; $c2++) {
                    $spanWidth += $this->columnWidths[$c2];
                }

                $textWidth = max(1.0, $spanWidth - $pl - $pr);

                if ($cell->getSpans() !== null) {
                    // Rich-text cell: font is carried per-span; use the largest
                    // span fontSize for cap-height and descender padding offsets.
                    $maxFontSize = 0.0;

                    foreach ($cell->getSpans() as $span) {
                        if ($span->getFontSize() <= $maxFontSize) {
                            continue;
                        }

                        $maxFontSize = $span->getFontSize();
                    }

                    $fontSize = $maxFontSize > 0.0
                        ? $maxFontSize
                        : $this->defaultFontSize;
                    $fontName = '';

                    $textBox = RichTextBox::create(
                        spans: $cell->getSpans(),
                        maxWidth: $textWidth,
                        align: $cell->getAlign(),
                    );
                } else {
                    $metrics = $cell->getMetrics() ?? $this->defaultMetrics;
                    $fontSize = $cell->getFontSize() ?? $this->defaultFontSize;
                    $fontName = $cell->getFontName() ?? $this->defaultFontName;

                    $textBox = TextBox::create(
                        text: $cell->getText(),
                        metrics: $metrics,
                        fontSize: $fontSize,
                        maxWidth: $textWidth,
                        align: $cell->getAlign(),
                    );
                }

                // paddingTop / paddingBottom are visual gaps: top of caps to
                // cell edge, and descender bottom to cell edge, respectively.
                // Convert to baseline offsets by adding font-metric approximations.
                $pt = ($cell->getPaddingTop() ?? $this->defaultPaddingTop) + $fontSize * 0.72;
                $pb = ($cell->getPaddingBottom() ?? $this->defaultPaddingBottom) + $fontSize * 0.20;

                $placementData[$ri][$ci] = [
                    'cell' => $data['cell'],
                    'colspan' => $data['colspan'],
                    'rowBg' => $data['rowBg'],
                    'rowspan' => $data['rowspan'],
                    'fontName' => $fontName,
                    'pb' => $pb,
                    'pl' => $pl,
                    'pr' => $pr,
                    'pt' => $pt,
                    'spanWidth' => $spanWidth,
                    'textBox' => $textBox,
                ];
            }
        }

        // ---- Phase 3: row heights --------------------------------------------

        $rowHeights = array_fill(0, $rowCount, 0.0);

        // Pass A: rowspan=1 cells set the minimum height for their single row.
        foreach ($placementData as $ri => $rowCells) {
            foreach ($rowCells as $ci => $data) {
                if ($data['rowspan'] !== 1) {
                    continue;
                }

                $minH = $data['pt'] + $this->contentHeight($data['textBox']) + $data['pb'];
                $rowHeights[$ri] = max($rowHeights[$ri], $minH);
            }
        }

        // Pass B: rowspan>1 cells — grow the last spanned row if the combined
        // height from pass A is not enough to contain the cell's content.
        foreach ($placementData as $ri => $rowCells) {
            foreach ($rowCells as $ci => $data) {
                if ($data['rowspan'] <= 1) {
                    continue;
                }

                $minH = $data['pt'] + $this->contentHeight($data['textBox']) + $data['pb'];
                $spanTotal = 0.0;

                for ($r2 = $ri; $r2 < $ri + $data['rowspan']; $r2++) {
                    $spanTotal += $rowHeights[$r2];
                }

                if ($minH <= $spanTotal) {
                    continue;
                }

                $rowHeights[$ri + $data['rowspan'] - 1] += $minH - $spanTotal;
            }
        }

        // ---- Phase 4: absolute positions -------------------------------------

        /** @var array<int, float> $rowY */
        $rowY = [];
        $currentY = $this->y;

        for ($ri = 0; $ri < $rowCount; $ri++) {
            $rowY[$ri] = $currentY;
            $currentY -= $rowHeights[$ri];
        }

        $tableBottom = $currentY;

        /** @var array<int, float> $colX */
        $colX = [];
        $cx = $this->x;

        for ($ci = 0; $ci < $colCount; $ci++) {
            $colX[$ci] = $cx;
            $cx += $this->columnWidths[$ci];
        }

        // Build flat placement list with all per-cell rendering data.
        /** @var list<Placement> $placements */
        $placements = [];

        foreach ($placementData as $ri => $rowCells) {
            foreach ($rowCells as $ci => $data) {
                $cellHeight = 0.0;

                for ($r2 = $ri; $r2 < $ri + $data['rowspan']; $r2++) {
                    $cellHeight += $rowHeights[$r2];
                }

                $placements[] = [
                    'cell' => $data['cell'],
                    'colspan' => $data['colspan'],
                    'colStart' => $ci,
                    'fontName' => $data['fontName'],
                    'height' => $cellHeight,
                    'pb' => $data['pb'],
                    'pl' => $data['pl'],
                    'pr' => $data['pr'],
                    'pt' => $data['pt'],
                    'rowBg' => $data['rowBg'],
                    'rowspan' => $data['rowspan'],
                    'rowStart' => $ri,
                    'textBox' => $data['textBox'],
                    'width' => $data['spanWidth'],
                    'x' => $colX[$ci],
                    'y' => $rowY[$ri],
                ];
            }
        }

        return [
            'tableBottom' => $tableBottom,
            'rowHeights' => $rowHeights,
            'rowY' => $rowY,
            'colX' => $colX,
            'placements' => $placements,
            'occupiedBy' => $occupiedBy,
        ];
    }

    /**
     * Distance from the first baseline to the last baseline of a text box.
     *
     * getHeight() = lineCount × lineHeight, which runs one lineHeight past the
     * last baseline (trailing leading). The true span from first to last
     * baseline is therefore getHeight() − lineHeight.
     */
    private function contentHeight(TextBox|RichTextBox $textBox): float
    {
        return max(0.0, $textBox->getHeight() - $textBox->getLineHeight());
    }

    /**
     * Computes the y coordinate of the first text baseline inside a cell,
     * taking vertical alignment into account.
     *
     * Top: first baseline at paddingTop below the cell's top edge.
     * Middle: the span from first to last baseline is centred between the
     *         padding edges.
     * Bottom: the last baseline sits at paddingBottom above the cell's bottom
     *         edge; the first baseline is above it by (lineCount−1)×lineHeight.
     *
     * @param Placement $p
     */
    private function textBaselineY(array $p): float
    {
        $available = $p['height'] - $p['pt'] - $p['pb'];
        $contentHeight = $this->contentHeight($p['textBox']);
        $extra = max(0.0, $available - $contentHeight);

        return match ($p['cell']->getVerticalAlign()) {
            TableVerticalAlign::Top => $p['y'] - $p['pt'],
            TableVerticalAlign::Middle => $p['y'] - $p['pt'] - $extra / 2.0,
            TableVerticalAlign::Bottom => $p['y'] - $p['pt'] - $extra,
        };
    }
}
