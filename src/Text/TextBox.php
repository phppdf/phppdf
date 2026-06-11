<?php

declare(strict_types=1);

namespace PhpPdf\Text;

use PhpPdf\Font\FontMetrics;

use const PREG_SPLIT_NO_EMPTY;

/**
 * An immutable text-layout value object.
 *
 * Wraps a UTF-8 text string into lines that fit within a given width using
 * greedy word wrapping. Newline characters (\n) in the source text become
 * explicit line breaks; consecutive blank lines are preserved as empty entries
 * in the line list so paragraph spacing can be rendered naturally.
 *
 * All measurement is done at construction time via the supplied FontMetrics.
 * The resulting object is stateless and safe to reuse.
 *
 * Usage:
 *
 *   $metrics = Type1FontMetrics::helvetica();
 *
 *   $box = TextBox::create(
 *       text: 'The quick brown fox jumps over the lazy dog.',
 *       metrics: $metrics,
 *       fontSize: 12,
 *       maxWidth: 300,
 *       lineHeight: 16,
 *       align: TextAlign::Left,
 *   );
 *
 *   $stream->drawTextBox($box, fontName: 'F1', x: 72, y: 720);
 *
 *   // Reserve vertical space for a subsequent element:
 *   $nextY = 720 - $box->getHeight();
 */
final class TextBox
{
    /** @var array<string> */
    private array $lines;

    /** @param array<string>|null $preLines Pre-computed lines; skips wrapping when supplied. */
    private function __construct(
        private readonly FontMetrics $metrics,
        private readonly float $fontSize,
        private readonly float $maxWidth,
        private readonly float $lineHeight,
        private readonly TextAlign $align,
        string $text,
        ?array $preLines = null,
        private readonly ?Hyphenator $hyphenator = null,
    ) {
        $this->lines = $preLines ?? $this->wrap($text);
    }

    /**
     * Creates a TextBox, computing wrapped lines immediately.
     *
     * @param float $lineHeight Baseline-to-baseline distance in points.
     *                                     Defaults to fontSize × 1.2 when 0 or negative.
     * @param \PhpPdf\Text\Hyphenator|null $hyphenator Optional hyphenator for splitting words at
     *                                     line boundaries. See TeXHyphenator.
     */
    public static function create(
        string $text,
        FontMetrics $metrics,
        float $fontSize,
        float $maxWidth,
        float $lineHeight = 0,
        TextAlign $align = TextAlign::Left,
        ?Hyphenator $hyphenator = null,
    ): self {
        return new self(
            $metrics,
            $fontSize,
            $maxWidth,
            $lineHeight > 0 ? $lineHeight : $fontSize * 1.2,
            $align,
            $text,
            null,
            $hyphenator,
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /** @return array<string> All wrapped lines; empty strings represent blank lines. */
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * Returns only the lines that fit within $maxHeight points.
     *
     * @return array<string>
     */
    public function linesFor(float $maxHeight): array
    {
        $max = max(1, (int) floor($maxHeight / $this->lineHeight));

        return array_slice($this->lines, 0, $max);
    }

    /** Total height in points occupied by all lines. */
    public function getHeight(): float
    {
        return count($this->lines) * $this->lineHeight;
    }

    public function getLineHeight(): float
    {
        return $this->lineHeight;
    }

    public function getFontSize(): float
    {
        return $this->fontSize;
    }

    public function getMaxWidth(): float
    {
        return $this->maxWidth;
    }

    public function getAlign(): TextAlign
    {
        return $this->align;
    }

    public function getMetrics(): FontMetrics
    {
        return $this->metrics;
    }

    /**
     * Returns true when the laid-out text is taller than $maxHeight.
     * Useful for detecting overflow before drawing.
     */
    public function overflows(float $maxHeight): bool
    {
        return $this->getHeight() > $maxHeight;
    }

    /**
     * Returns the advance width of $line in points (not glyph-space units).
     * Useful when computing per-line offsets for alignment.
     */
    public function lineWidthPt(string $line): float
    {
        return $this->metrics->stringWidth($line) * $this->fontSize / 1000;
    }

    /**
     * Returns a new TextBox that begins at line $lineCount, sharing the same
     * metrics. Use with linesFor() to implement multi-page text flow:
     *
     *   $remaining = $box;
     *   while ($remaining->getLines() !== []) {
     *       $n = count($remaining->linesFor($maxHeight));
     *       // draw $remaining on a new page with $maxHeight …
     *       $remaining = $remaining->skip($n);
     *   }
     */
    public function skip(int $lineCount): self
    {
        return self::withLines(
            $this->metrics,
            $this->fontSize,
            $this->maxWidth,
            $this->lineHeight,
            $this->align,
            array_slice($this->lines, $lineCount),
            $this->hyphenator,
        );
    }

    /** @return array<string> */
    private function wrap(string $text): array
    {
        // Convert max width from points to glyph-space units.
        $maxUnits = $this->maxWidth * 1000 / $this->fontSize;
        $spaceWidth = $this->metrics->charWidth(32); // U+0020 SPACE
        $hyphenWidth = $this->hyphenator !== null
            ? $this->metrics->charWidth(0x2D) // U+002D HYPHEN-MINUS
            : 0.0;

        $lines = [];

        foreach (explode("\n", $text) as $paragraph) {
            $paragraph = rtrim($paragraph);

            if ($paragraph === '') {
                $lines[] = ''; // blank line — preserve paragraph spacing

                continue;
            }

            // Split on any whitespace, discarding empty tokens.
            $words = preg_split('/\s+/', $paragraph, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $currentLine = '';
            $currentUnits = 0.0;

            foreach ($words as $word) {
                $wordUnits = $this->metrics->stringWidth($word);

                if ($currentLine === '') {
                    // Always place at least one word, even if it exceeds maxWidth.
                    $currentLine = $word;
                    $currentUnits = $wordUnits;
                } elseif ($currentUnits + $spaceWidth + $wordUnits <= $maxUnits) {
                    $currentLine .= ' ' . $word;
                    $currentUnits += $spaceWidth + $wordUnits;
                } else {
                    // Word does not fit. Try hyphenating it into the remaining space.
                    if ($this->hyphenator !== null) {
                        $available = $maxUnits - $currentUnits - $spaceWidth;
                        $parts = $this->hyphenator->breakWord($word);

                        if (count($parts) > 1 && $available > $hyphenWidth) {
                            $prefix = '';
                            $prefixUnits = 0.0;
                            $splitAt = -1;

                            foreach ($parts as $k => $part) {
                                if ($k === count($parts) - 1) {
                                    break; // never break after the last fragment
                                }

                                $candidate = $prefix . $part;
                                $candidateUnits = $this->metrics->stringWidth($candidate);

                                if ($candidateUnits + $hyphenWidth > $available) {
                                    break;
                                }

                                $splitAt = $k;
                                $prefix = $candidate;
                                $prefixUnits = $candidateUnits;
                            }

                            if ($splitAt >= 0) {
                                $lines[] = $currentLine . ' ' . $prefix . '-';
                                $remainder = implode('', array_slice($parts, $splitAt + 1));
                                $currentLine = $remainder;
                                $currentUnits = $this->metrics->stringWidth($remainder);

                                continue;
                            }
                        }
                    }

                    $lines[] = $currentLine;
                    $currentLine = $word;
                    $currentUnits = $wordUnits;
                }
            }

            if ($currentLine === '') {
                continue;
            }

            $lines[] = $currentLine;
        }

        return $lines;
    }





    /** @param array<string> $lines */
    private static function withLines(
        FontMetrics $metrics,
        float $fontSize,
        float $maxWidth,
        float $lineHeight,
        TextAlign $align,
        array $lines,
        ?Hyphenator $hyphenator = null,
    ): self {
        return new self($metrics, $fontSize, $maxWidth, $lineHeight, $align, '', $lines, $hyphenator);
    }

    // -------------------------------------------------------------------------
    // Layout engine
    // -------------------------------------------------------------------------
}
