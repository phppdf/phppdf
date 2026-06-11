<?php

declare(strict_types=1);

namespace PhpPdf\Font;

/**
 * FontMetrics adapter for embedded TrueType / OpenType fonts.
 *
 * Delegates to TrueTypeFont::getGlyphId() and TrueTypeFont::getAdvanceWidth()
 * so that text measurement is accurate for any embedded font, including CJK
 * fonts with thousands of distinct character widths.
 *
 * Usage:
 *   $font = TrueTypeFont::fromFile('/path/to/font.ttf');
 *   $metrics = TrueTypeFontMetrics::fromFont($font);
 *   $box = TextBox::create('Hello', $metrics, fontSize: 14, maxWidth: 400);
 */
final class TrueTypeFontMetrics implements FontMetrics
{
    private function __construct(private readonly TrueTypeFont $font,)
    {
    }

    public static function fromFont(TrueTypeFont $font): self
    {
        return new self($font);
    }

    public function charWidth(int $codePoint): float
    {
        $glyphId = $this->font->getGlyphId($codePoint);

        return (float) $this->font->toPdfUnits($this->font->getAdvanceWidth($glyphId));
    }

    public function stringWidth(string $text): float
    {
        $total = 0.0;
        $len = mb_strlen($text, 'UTF-8');

        for ($i = 0; $i < $len; $i++) {
            $cp = mb_ord(mb_substr($text, $i, 1, 'UTF-8'), 'UTF-8');
            $total += $this->charWidth($cp);
        }

        return $total;
    }
}
