<?php

declare(strict_types=1);

namespace PhpPdf\Text;

use const PREG_SPLIT_NO_EMPTY;

/**
 * Knuth-Liang TeX hyphenation algorithm.
 *
 * Accepts pattern strings in standard TeX format (e.g. "hy3ph", ".ach4").
 * Digits in a pattern represent weights at the inter-character positions
 * surrounding the adjacent letter. Positions with an odd maximum weight are
 * valid hyphenation points; even weights suppress breaking.
 *
 * Load patterns from a .tex file and pass them to the constructor:
 *
 *   $hyphenator = new TeXHyphenator(
 *       file(__DIR__ . '/resources/hyphenation/en-US.tex', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
 *   );
 *
 * @see https://tug.org/docs/liang/ (Liang's original dissertation)
 */
final class TeXHyphenator implements Hyphenator
{
    /** @var array<string,array<int>> Parsed patterns: letters → weight array (length+1). */
    private array $patterns;

    /**
     * Minimum number of characters to keep before the first hyphen.
     * Standard TeX default is 2.
     */
    private int $leftMin;

    /**
     * Minimum number of characters to keep after the last hyphen.
     * Standard TeX default is 3.
     */
    private int $rightMin;

    /**
     * @param array<string> $rawPatterns Pattern strings in TeX format, e.g. ["hy3ph", ".ach4"].
     * @param int $leftMin     Minimum characters before the first break (≥1).
     * @param int $rightMin    Minimum characters after the last break (≥1).
     */
    public function __construct(array $rawPatterns, int $leftMin = 2, int $rightMin = 3,)
    {
        $this->leftMin = max(1, $leftMin);
        $this->rightMin = max(1, $rightMin);
        $this->patterns = self::parsePatterns($rawPatterns);
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /** @return list<string> */
    public function breakWord(string $word): array
    {
        $word = mb_strtolower($word, 'UTF-8');
        $len = mb_strlen($word, 'UTF-8');

        // Too short to hyphenate given the margin requirements.
        if ($len < $this->leftMin + $this->rightMin) {
            return [$word];
        }

        // Pad with word-boundary markers for pattern matching.
        $padded = '.' . $word . '.';
        $padLen = $len + 2;

        // weight[$i] = weight *before* character $i of $padded.
        $weight = array_fill(0, $padLen + 1, 0);

        foreach ($this->patterns as $letters => $patWeights) {
            $pos = 0;

            while (($idx = mb_strpos($padded, $letters, $pos, 'UTF-8')) !== false) {
                foreach ($patWeights as $offset => $w) {
                    $weight[$idx + $offset] = max($weight[$idx + $offset], $w);
                }

                $pos = $idx + 1;
            }
        }

        // Collect valid break points inside the word.
        // The word starts at index 1 in $padded; breaks between word chars at
        // padded indices 1..($len−1), i.e. weight indices 2..($len).
        $breaks = [];

        for ($i = 0; $i < $len - 1; $i++) {
            $charPos = $i + 1; // 0-indexed position in the word

            if ($charPos < $this->leftMin || ($len - $charPos) < $this->rightMin || ($weight[$charPos + 1] % 2) !== 1) {
                continue;
            }

            $breaks[] = $charPos;
        }

        if ($breaks === []) {
            return [$word];
        }

        // Split the word at each break point.
        $parts = [];
        $prev = 0;

        foreach ($breaks as $bp) {
            $parts[] = mb_substr($word, $prev, $bp - $prev, 'UTF-8');
            $prev = $bp;
        }

        $parts[] = mb_substr($word, $prev, null, 'UTF-8');

        return $parts;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Parses TeX-format pattern strings into a letters→weights map.
     *
     * @param array<string> $raw
     * @return array<string,array<int>>
     */
    private static function parsePatterns(array $raw): array
    {
        $result = [];

        foreach ($raw as $pattern) {
            $letters = '';
            $weights = [0];

            $chars = preg_split('//u', $pattern, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($chars as $ch) {
                if ($ch >= '0' && $ch <= '9') {
                    $weights[count($weights) - 1] = (int) $ch;
                } else {
                    $letters .= $ch;
                    $weights[] = 0;
                }
            }

            $result[$letters] = $weights;
        }

        return $result;
    }
}
