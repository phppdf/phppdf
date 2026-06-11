<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal;

use PhpPdf\Text\TextAlign;

/**
 * The fully resolved style for a single DOM node.
 *
 * Produced by StyleResolver::resolve() after applying browser defaults,
 * stylesheet rules, and inline style attributes. Inheritable properties
 * (font-family, font-size, font-weight, font-style, color, text-align,
 * line-height) are copied from the parent by default; box properties
 * (margins, padding, background) are reset to zero/null.
 *
 * The $fontFamily field holds the *primary* name of the font family as
 * registered in HtmlConverterConfig (e.g. 'helvetica', 'times-roman').
 * HtmlLayoutEngine translates that name + bold + italic flags into the
 * correct PDF resource name (F0N, F1B, …) and registers the font on each
 * page builder.
 */
final class ComputedStyle
{
    // ── Inherited typography ────────────────────────────────────────────────

    private string $fontFamily;
    private float $fontSize;
    private bool $bold = false;
    private bool $italic = false;

    /** @var array{float,float,float} */
    private array $color = [0.0, 0.0, 0.0];
    private TextAlign $textAlign = TextAlign::Left;
    private float $lineHeight = 0.0;

    // ── Non-inherited box model ─────────────────────────────────────────────

    /** @var array{float,float,float}|null */
    private ?array $backgroundColor = null;
    private float $marginTop = 0.0;
    private float $marginBottom = 0.0;
    private float $marginLeft = 0.0;
    private float $paddingLeft = 0.0;

    public function __construct(string $fontFamily, float $fontSize)
    {
        $this->fontFamily = $fontFamily;
        $this->fontSize = $fontSize;
    }

    // ── Typography getters/setters ──────────────────────────────────────────

    public function getFontFamily(): string
    {
        return $this->fontFamily;
    }

    public function setFontFamily(string $fontFamily): void
    {
        $this->fontFamily = $fontFamily;
    }

    public function getFontSize(): float
    {
        return $this->fontSize;
    }

    public function setFontSize(float $fontSize): void
    {
        $this->fontSize = $fontSize;
    }

    public function isBold(): bool
    {
        return $this->bold;
    }

    public function setBold(bool $bold): void
    {
        $this->bold = $bold;
    }

    public function isItalic(): bool
    {
        return $this->italic;
    }

    public function setItalic(bool $italic): void
    {
        $this->italic = $italic;
    }

    /** @return array{float,float,float} */
    public function getColor(): array
    {
        return $this->color;
    }

    /** @param array{float,float,float} $color */
    public function setColor(array $color): void
    {
        $this->color = $color;
    }

    public function getTextAlign(): TextAlign
    {
        return $this->textAlign;
    }

    public function setTextAlign(TextAlign $align): void
    {
        $this->textAlign = $align;
    }

    public function getLineHeight(): float
    {
        return $this->lineHeight;
    }

    public function setLineHeight(float $lineHeight): void
    {
        $this->lineHeight = $lineHeight;
    }

    // ── Box model getters/setters ───────────────────────────────────────────

    /** @return array{float,float,float}|null */
    public function getBackgroundColor(): ?array
    {
        return $this->backgroundColor;
    }

    /** @param array{float,float,float}|null $color */
    public function setBackgroundColor(?array $color): void
    {
        $this->backgroundColor = $color;
    }

    public function getMarginTop(): float
    {
        return $this->marginTop;
    }

    public function setMarginTop(float $margin): void
    {
        $this->marginTop = $margin;
    }

    public function getMarginBottom(): float
    {
        return $this->marginBottom;
    }

    public function setMarginBottom(float $margin): void
    {
        $this->marginBottom = $margin;
    }

    public function getMarginLeft(): float
    {
        return $this->marginLeft;
    }

    public function setMarginLeft(float $margin): void
    {
        $this->marginLeft = $margin;
    }

    public function getPaddingLeft(): float
    {
        return $this->paddingLeft;
    }

    public function setPaddingLeft(float $padding): void
    {
        $this->paddingLeft = $padding;
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Returns the effective baseline-to-baseline distance in points.
     *
     * Uses the explicit lineHeight if set (> 0), otherwise falls back to
     * fontSize × $multiplier.
     */
    public function effectiveLineHeight(float $multiplier = 1.4): float
    {
        return $this->lineHeight > 0.0
            ? $this->lineHeight
            : $this->fontSize * $multiplier;
    }
}
