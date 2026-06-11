<?php

declare(strict_types=1);

namespace PhpPdf\Table;

use PhpPdf\Color\Color;
use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\TextAlign;

/**
 * Configuration for a single table cell.
 *
 * All settings are optional; unset values fall back to the TableBuilder defaults.
 * Chain the fluent setters to override per-cell:
 *
 *   TableCell::text('Total')
 *       ->background(Color::fromHex('#1a3a5c'))
 *       ->textColor(Color::white())
 *       ->align(TextAlign::Right)
 *       ->verticalAlign(TableVerticalAlign::Middle)
 *       ->colspan(2)
 *       ->padding(8, 6, 8, 6)
 */
final class TableCell
{
    /** Background fill for the cell; null falls back to the row background or no fill. */
    private ?Color $background = null;

    /** Text ink colour; null falls back to the current page fill colour. */
    private ?Color $textColor = null;

    /** Horizontal alignment of text lines within the cell. */
    private TextAlign $align = TextAlign::Left;

    /** Vertical placement of the text block within the cell height. */
    private TableVerticalAlign $verticalAlign = TableVerticalAlign::Top;

    /** Number of columns this cell spans (≥ 1). */
    private int $colspan = 1;

    /** Number of rows this cell spans (≥ 1). */
    private int $rowspan = 1;

    /** Top padding override in points; null falls back to the TableBuilder default. */
    private ?float $paddingTop = null;

    /** Right padding override in points; null falls back to the TableBuilder default. */
    private ?float $paddingRight = null;

    /** Bottom padding override in points; null falls back to the TableBuilder default. */
    private ?float $paddingBottom = null;

    /** Left padding override in points; null falls back to the TableBuilder default. */
    private ?float $paddingLeft = null;

    /** PDF resource name of the cell font; null falls back to the TableBuilder default. */
    private ?string $fontName = null;

    /** Font size in points for this cell; null falls back to the TableBuilder default. */
    private ?float $fontSize = null;

    /** Font metrics for this cell; null falls back to the TableBuilder default. */
    private ?FontMetrics $metrics = null;

    /**
     * Rich-text spans for this cell; null means use the plain $text path.
     *
     * When set, the cell-level font() override is ignored — each span carries
     * its own font name, size, and metrics.
     *
     * @var list<\PhpPdf\Text\TextSpan>|null
     */
    private ?array $textSpans = null;

    /** Use TableCell::text() or TableCell::spans() to construct instances. */
    private function __construct(private string $text)
    {
    }

    /** Creates a cell with the given text content. */
    public static function text(string $text): self
    {
        return new self($text);
    }

    /**
     * Creates a cell whose content is composed of multiple text spans, each
     * optionally using a different font.
     *
     * Use this instead of text() when you need to mix fonts within a single
     * cell. The cell-level font() override does not apply to span cells; each
     * span carries its own font name, size, and metrics.
     *
     * @param list<\PhpPdf\Text\TextSpan> $spans
     */
    public static function spans(array $spans): self
    {
        $cell = new self('');
        $cell->textSpans = $spans;

        return $cell;
    }

    /** Sets the background fill colour for this cell. */
    public function background(Color $color): self
    {
        $this->background = $color;

        return $this;
    }

    /** Sets the text ink colour for this cell. */
    public function textColor(Color $color): self
    {
        $this->textColor = $color;

        return $this;
    }

    /** Horizontal alignment of text lines within the cell. */
    public function align(TextAlign $align): self
    {
        $this->align = $align;

        return $this;
    }

    /** Vertical placement of the text block within the cell height. */
    public function verticalAlign(TableVerticalAlign $align): self
    {
        $this->verticalAlign = $align;

        return $this;
    }

    /**
     * Number of columns this cell spans (≥ 1).
     *
     * Cells after this one in the same row are shifted right accordingly.
     * The cell's text width equals the sum of the spanned column widths
     * minus padding.
     */
    public function colspan(int $span): self
    {
        $this->colspan = max(1, $span);

        return $this;
    }

    /**
     * Number of rows this cell spans (≥ 1).
     *
     * The cell's height equals the sum of the spanned row heights. Positions
     * in subsequent rows covered by this span are skipped automatically.
     */
    public function rowspan(int $span): self
    {
        $this->rowspan = max(1, $span);

        return $this;
    }

    /**
     * Per-cell padding override (top, right, bottom, left — same order as CSS).
     *
     * $top — visual gap from the cell's top edge to the top of capital letters.
     * $bottom — visual gap from the bottom of descenders to the cell's bottom edge.
     * $left / $right — gap from the cell edge to the start/end of text lines.
     */
    public function padding(float $top, float $right, float $bottom, float $left): self
    {
        $this->paddingTop = $top;
        $this->paddingRight = $right;
        $this->paddingBottom = $bottom;
        $this->paddingLeft = $left;

        return $this;
    }

    /** Per-cell font override. Use when a single cell needs a different typeface or size. */
    public function font(string $name, float $size, FontMetrics $metrics): self
    {
        $this->fontName = $name;
        $this->fontSize = $size;
        $this->metrics = $metrics;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Accessors — consumed by TableBuilder
    // -------------------------------------------------------------------------

    /** Returns the cell's text content. */
    public function getText(): string
    {
        return $this->text;
    }

    /** Returns the background fill colour, or null if not set. */
    public function getBackground(): ?Color
    {
        return $this->background;
    }

    /** Returns the text ink colour, or null if not set. */
    public function getTextColor(): ?Color
    {
        return $this->textColor;
    }

    /** Returns the horizontal text alignment. */
    public function getAlign(): TextAlign
    {
        return $this->align;
    }

    /** Returns the vertical text alignment. */
    public function getVerticalAlign(): TableVerticalAlign
    {
        return $this->verticalAlign;
    }

    /** Returns the column span (≥ 1). */
    public function getColspan(): int
    {
        return $this->colspan;
    }

    /** Returns the row span (≥ 1). */
    public function getRowspan(): int
    {
        return $this->rowspan;
    }

    /** Returns the top padding override in points, or null to use the TableBuilder default. */
    public function getPaddingTop(): ?float
    {
        return $this->paddingTop;
    }

    /** Returns the right padding override in points, or null to use the TableBuilder default. */
    public function getPaddingRight(): ?float
    {
        return $this->paddingRight;
    }

    /** Returns the bottom padding override in points, or null to use the TableBuilder default. */
    public function getPaddingBottom(): ?float
    {
        return $this->paddingBottom;
    }

    /** Returns the left padding override in points, or null to use the TableBuilder default. */
    public function getPaddingLeft(): ?float
    {
        return $this->paddingLeft;
    }

    /** Returns the PDF resource name of the font override, or null to use the TableBuilder default. */
    public function getFontName(): ?string
    {
        return $this->fontName;
    }

    /** Returns the font size override in points, or null to use the TableBuilder default. */
    public function getFontSize(): ?float
    {
        return $this->fontSize;
    }

    /** Returns the font metrics override, or null to use the TableBuilder default. */
    public function getMetrics(): ?FontMetrics
    {
        return $this->metrics;
    }

    /**
     * Returns the list of rich-text spans, or null if this is a plain-text cell.
     *
     * @return list<\PhpPdf\Text\TextSpan>|null
     */
    public function getSpans(): ?array
    {
        return $this->textSpans;
    }
}
