<?php

declare(strict_types=1);

namespace PhpPdf\Text;

use const PREG_SPLIT_NO_EMPTY;

/**
 * An immutable rich-text layout value object.
 *
 * Wraps a list of TextSpan values into lines that fit within a given width
 * using greedy word wrapping. Each span carries its own font name, size, and
 * metrics, so multiple typefaces may appear within a single text box — for
 * example, mixing regular and bold text on the same line.
 *
 * When two adjacent words on the same line come from spans with different
 * fonts, the inter-word space is appended to the first word's span so that
 * the PDF glyph advance is measured in that font.
 *
 * Line height defaults to the largest (fontSize × 1.2) among all input spans,
 * ensuring glyphs of every size fit comfortably. Override via the $lineHeight
 * parameter when a fixed rhythm is required.
 *
 * Usage:
 *
 *   $box = RichTextBox::create(
 *       spans: [
 *           TextSpan::create('Invoice: ', 'F1', 10, $regular),
 *           TextSpan::create('INV-2024-001', 'F2', 10, $bold),
 *       ],
 *       maxWidth: 200,
 *   );
 *
 *   $stream->drawRichTextBox($box, x: 72, y: 720);
 */
final class RichTextBox
{
    /** @var list<list<\PhpPdf\Text\TextSpan>> Wrapped lines; each line is a list of TextSpan values. */
    private array $lines;

    /**
     * @param list<\PhpPdf\Text\TextSpan> $spans    Source spans (used by wrap()).
     * @param list<list<\PhpPdf\Text\TextSpan>>|null $preLines Pre-computed lines; skips wrapping when supplied.
     */
    private function __construct(
        private readonly float $maxWidth,
        private readonly float $lineHeight,
        private readonly TextAlign $align,
        array $spans,
        ?array $preLines = null,
        private readonly ?Hyphenator $hyphenator = null,
    ) {
        $this->lines = $preLines ?? $this->wrap($spans);
    }

    /**
     * Creates a RichTextBox, computing wrapped lines immediately.
     *
     * @param list<\PhpPdf\Text\TextSpan> $spans
     * @param float $lineHeight Baseline-to-baseline distance in points.
     *                           When 0 or negative, defaults to the largest
     *                           (fontSize × 1.2) among all spans, or 12 pt when
     *                           $spans is empty.
     */
    public static function create(
        array $spans,
        float $maxWidth,
        float $lineHeight = 0,
        TextAlign $align = TextAlign::Left,
        ?Hyphenator $hyphenator = null,
    ): self {
        if ($lineHeight <= 0) {
            $maxSize = 0.0;

            foreach ($spans as $span) {
                if ($span->getFontSize() <= $maxSize) {
                    continue;
                }

                $maxSize = $span->getFontSize();
            }

            $lineHeight = $maxSize > 0
                ? $maxSize * 1.2
                : 12.0;
        }

        return new self($maxWidth, $lineHeight, $align, $spans, null, $hyphenator);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Returns all wrapped lines; each line is a list of TextSpan values.
     *
     * An empty inner list represents a line with no renderable content.
     *
     * @return list<list<\PhpPdf\Text\TextSpan>>
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * Returns only the lines that fit within $maxHeight points.
     *
     * @return list<list<\PhpPdf\Text\TextSpan>>
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

    /** Baseline-to-baseline distance in points. */
    public function getLineHeight(): float
    {
        return $this->lineHeight;
    }

    /** Maximum line width in points, as passed to create(). */
    public function getMaxWidth(): float
    {
        return $this->maxWidth;
    }

    /** Horizontal alignment applied during rendering. */
    public function getAlign(): TextAlign
    {
        return $this->align;
    }

    /**
     * Returns the total advance width of a line in points.
     *
     * Sums the widthPt() of every span in the line, including any inter-span
     * trailing spaces that were added during wrapping.
     *
     * @param list<\PhpPdf\Text\TextSpan> $line
     */
    public function lineWidthPt(array $line): float
    {
        $width = 0.0;

        foreach ($line as $span) {
            $width += $span->widthPt();
        }

        return $width;
    }

    /**
     * Returns a new RichTextBox starting at line $lineCount, sharing the same
     * layout parameters. Use with linesFor() for multi-page text flow.
     */
    public function skip(int $lineCount): self
    {
        return new self(
            $this->maxWidth,
            $this->lineHeight,
            $this->align,
            [],
            array_slice($this->lines, $lineCount),
            $this->hyphenator,
        );
    }

    // -------------------------------------------------------------------------
    // Layout engine
    // -------------------------------------------------------------------------

    /**
     * Greedy word-wrap across spans.
     *
     * Tokenises all span texts into words, then accumulates words onto lines.
     * Adjacent tokens with the same (fontName, fontSize) pair are merged into
     * a single span joined by a space. When adjacent tokens differ in font, the
     * inter-word space is appended to the first token's text so that the PDF
     * advance is measured in the correct font.
     *
     * @param list<\PhpPdf\Text\TextSpan> $spans
     * @return list<list<\PhpPdf\Text\TextSpan>>
     */
    private function wrap(array $spans): array
    {
        // Each token: ['word'=>string, 'fontName'=>string, 'fontSize'=>float, 'metrics'=>FontMetrics]
        $tokens = [];

        foreach ($spans as $span) {
            $words = preg_split('/\s+/', $span->getText(), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($words as $word) {
                $tokens[] = [
                    'fontName' => $span->getFontName(),
                    'fontSize' => $span->getFontSize(),
                    'metrics' => $span->getMetrics(),
                    'word' => $word,
                ];
            }
        }

        if ($tokens === []) {
            return [[]]; // one empty line
        }

        $lines = [];
        $lineTokens = [];
        $lineWidthPt = 0.0;

        foreach ($tokens as $token) {
            $wordPt = $token['metrics']->stringWidth($token['word']) * $token['fontSize'] / 1000;
            $spacePt = $token['metrics']->charWidth(32) * $token['fontSize'] / 1000;

            if ($lineTokens === []) {
                $lineTokens[] = $token;
                $lineWidthPt = $wordPt;
            } elseif ($lineWidthPt + $spacePt + $wordPt <= $this->maxWidth) {
                $lineTokens[] = $token;
                $lineWidthPt += $spacePt + $wordPt;
            } else {
                if ($this->hyphenator !== null) {
                    $parts = $this->hyphenator->breakWord($token['word']);
                    $hyphenPt = $token['metrics']->charWidth(ord('-')) * $token['fontSize'] / 1000;
                    $available = $this->maxWidth - $lineWidthPt - $spacePt;

                    if (count($parts) > 1 && $available > $hyphenPt) {
                        $fitted = '';

                        foreach ($parts as $part) {
                            $candidatePt = $token['metrics']->stringWidth($fitted . $part) * $token['fontSize'] / 1000;

                            if ($candidatePt + $hyphenPt > $available) {
                                break;
                            }

                            $fitted .= $part;
                        }

                        if ($fitted !== '') {
                            $remainder = mb_substr($token['word'], mb_strlen($fitted, 'UTF-8'), null, 'UTF-8');

                            $lineTokens[] = [
                                'word' => $fitted . '-',
                                'fontName' => $token['fontName'],
                                'fontSize' => $token['fontSize'],
                                'metrics' => $token['metrics'],
                            ];

                            $lines[] = $this->mergeTokensIntoSpans($lineTokens);

                            $lineTokens = [
                                [
                                    'word' => $remainder,
                                    'fontName' => $token['fontName'],
                                    'fontSize' => $token['fontSize'],
                                    'metrics' => $token['metrics'],
                                ],
                            ];

                            $lineWidthPt = $token['metrics']->stringWidth($remainder) * $token['fontSize'] / 1000;

                            continue;
                        }
                    }
                }

                $lines[] = $this->mergeTokensIntoSpans($lineTokens);
                $lineTokens = [$token];
                $lineWidthPt = $wordPt;
            }
        }

        $lines[] = $this->mergeTokensIntoSpans($lineTokens);

        return $lines;
    }

    /**
     * Merges adjacent tokens that share the same (fontName, fontSize) into single
     * TextSpan values, joining their words with a space. When two adjacent tokens
     * differ in font, a trailing space is appended to the first token's text so
     * that the inter-word gap is measured and rendered in the correct font.
     *
     * @param list<array{word:string,fontName:string,fontSize:float,metrics:\PhpPdf\Font\FontMetrics}> $tokens
     * @return list<\PhpPdf\Text\TextSpan>
     */
    private function mergeTokensIntoSpans(array $tokens): array
    {
        $spans = [];
        $run = null;

        foreach ($tokens as $token) {
            if ($run === null) {
                $run = $token;
            } elseif ($run['fontName'] === $token['fontName'] && $run['fontSize'] === $token['fontSize']) {
                $run['word'] .= ' ' . $token['word'];
            } else {
                // Different font: close current run with a trailing space so the
                // next span starts at the right position.
                $run['word'] .= ' ';
                $spans[] = TextSpan::create($run['word'], $run['fontName'], $run['fontSize'], $run['metrics']);
                $run = $token;
            }
        }

        if ($run !== null) {
            $spans[] = TextSpan::create($run['word'], $run['fontName'], $run['fontSize'], $run['metrics']);
        }

        return $spans;
    }
}
