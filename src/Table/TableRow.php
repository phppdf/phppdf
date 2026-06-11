<?php

declare(strict_types=1);

namespace PhpPdf\Table;

use PhpPdf\Color\Color;

/**
 * A row of TableCell values.
 *
 * A row-level background acts as a default for every cell in the row; a cell's
 * own background() takes precedence when set.
 *
 * Usage:
 *
 *   $row = TableRow::cells([
 *       TableCell::text('Widget A'),
 *       TableCell::text('A high-quality widget.'),
 *       TableCell::text('$19.99')->align(TextAlign::Right),
 *   ])->background(Color::fromHex('#f5f5f5'));
 */
final class TableRow
{
    private ?Color $background = null;

    /** @param list<\PhpPdf\Table\TableCell> $cells */
    private function __construct(private readonly array $cells)
    {
    }

    /** @param list<\PhpPdf\Table\TableCell> $cells */
    public static function cells(array $cells): self
    {
        return new self($cells);
    }

    /**
     * Sets a background colour for the entire row.
     *
     * Individual cells can still override this with their own background().
     */
    public function background(Color $color): self
    {
        $this->background = $color;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Accessors — consumed by TableBuilder
    // -------------------------------------------------------------------------

    /** @return list<\PhpPdf\Table\TableCell> */
    public function getCells(): array
    {
        return $this->cells;
    }

    /** Returns the row-level background colour, or null if none is set. */
    public function getBackground(): ?Color
    {
        return $this->background;
    }
}
