<?php

declare(strict_types=1);

namespace PhpPdf\Text;

/**
 * Splits a word at valid hyphenation break points.
 *
 * Implementations return the word as an ordered list of fragments; the caller
 * inserts a hyphen character between fragments when laying out text. Returning
 * a single-element array means the word cannot (or should not) be broken.
 *
 * Example: 'hyphenation' → ['hy', 'phen', 'a', 'tion']
 */
interface Hyphenator
{
    /** @return array<string> One or more fragments. A single-element result means no break. */
    public function breakWord(string $word): array;
}
