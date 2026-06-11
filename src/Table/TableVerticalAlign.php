<?php

declare(strict_types=1);

namespace PhpPdf\Table;

/**
 * Vertical alignment of text within a table cell.
 *
 * Top: first text baseline is paddingTop below the cell's top edge.
 * Middle: the text block is centred in the space between the padding edges.
 * Bottom: the last text position is paddingBottom above the cell's bottom edge.
 */
enum TableVerticalAlign
{
    /** First text baseline is paddingTop below the cell's top edge. */
    case Top;

    /** Text block is centred between the top and bottom padding edges. */
    case Middle;

    /** Last text baseline is paddingBottom above the cell's bottom edge. */
    case Bottom;
}
