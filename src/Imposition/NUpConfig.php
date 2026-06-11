<?php

declare(strict_types=1);

namespace PhpPdf\Imposition;

use InvalidArgumentException;

/**
 * Layout configuration for N-up imposition.
 *
 * Describes how many source pages are placed per output sheet, the output sheet
 * dimensions, and the spacing between cells. The origin of the coordinate system
 * is the bottom-left corner of the sheet (PDF default).
 *
 * Cell positions follow reading order: left-to-right, top-to-bottom.
 * Position 0 is the top-left cell, position (pagesPerSheet - 1) is bottom-right.
 *
 * Common preset layouts:
 *
 *   NUpConfig::twoUp(842, 595) // 2-up, A4 landscape sheet, 2 cols × 1 row
 *   NUpConfig::fourUp(595, 842) // 4-up, A4 portrait sheet, 2 cols × 2 rows
 *   NUpConfig::nineUp(595, 842) // 9-up, A4 portrait sheet, 3 cols × 3 rows
 *   NUpConfig::sixteenUp(595, 842) // 16-up,A4 portrait sheet, 4 cols × 4 rows
 */
final class NUpConfig
{
    public function __construct(
        public readonly int $cols,
        public readonly int $rows,
        public readonly int $sheetWidth,
        public readonly int $sheetHeight,
        public readonly float $margin = 18.0,
        public readonly float $gutter = 9.0,
    ) {
        if ($cols < 1 || $rows < 1) {
            throw new InvalidArgumentException('cols and rows must each be at least 1');
        }

        if ($sheetWidth < 1 || $sheetHeight < 1) {
            throw new InvalidArgumentException('sheet dimensions must be positive');
        }
    }

    // -------------------------------------------------------------------------
    // Preset layouts
    // -------------------------------------------------------------------------

    public static function twoUp(int $sheetWidth, int $sheetHeight, float $margin = 18.0, float $gutter = 9.0,): self
    {
        return new self(2, 1, $sheetWidth, $sheetHeight, $margin, $gutter);
    }

    public static function fourUp(int $sheetWidth, int $sheetHeight, float $margin = 18.0, float $gutter = 9.0,): self
    {
        return new self(2, 2, $sheetWidth, $sheetHeight, $margin, $gutter);
    }

    public static function nineUp(int $sheetWidth, int $sheetHeight, float $margin = 18.0, float $gutter = 9.0,): self
    {
        return new self(3, 3, $sheetWidth, $sheetHeight, $margin, $gutter);
    }

    public static function sixteenUp(
        int $sheetWidth,
        int $sheetHeight,
        float $margin = 18.0,
        float $gutter = 9.0,
    ): self {
        return new self(4, 4, $sheetWidth, $sheetHeight, $margin, $gutter);
    }

    // -------------------------------------------------------------------------
    // Geometry helpers
    // -------------------------------------------------------------------------

    /** Total pages placed per output sheet. */
    public function pagesPerSheet(): int
    {
        return $this->cols * $this->rows;
    }

    /** Usable width of a single cell in points. */
    public function cellWidth(): float
    {
        return ($this->sheetWidth - 2.0 * $this->margin - ($this->cols - 1) * $this->gutter) / $this->cols;
    }

    /** Usable height of a single cell in points. */
    public function cellHeight(): float
    {
        return ($this->sheetHeight - 2.0 * $this->margin - ($this->rows - 1) * $this->gutter) / $this->rows;
    }

    /**
     * Returns the bottom-left corner [x, y] of the cell at the given reading-order
     * position (0 = top-left, increments left-to-right then top-to-bottom).
     *
     * @return array{float, float}
     */
    public function cellOrigin(int $position): array
    {
        $col = $position % $this->cols;
        $rowLTR = intdiv($position, $this->cols); // 0 = top row (reading order)
        $pdfRow = $this->rows - 1 - $rowLTR; // flip: PDF y grows upward

        $x = $this->margin + $col * ($this->cellWidth() + $this->gutter);
        $y = $this->margin + $pdfRow * ($this->cellHeight() + $this->gutter);

        return [$x, $y];
    }
}
