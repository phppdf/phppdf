<?php

declare(strict_types=1);

namespace PhpPdf\Text\TextBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\Hyphenator;
use PhpPdf\Text\TextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextBox::class)]
#[CoversMethod(TextBox::class, 'create')]
final class WrapWithHyphenatorTest extends TestCase
{
    /**
     * Metrics that give every character width 100 glyph units so arithmetic
     * is easy: at fontSize=10, 1 char = 100/1000*10 = 1 pt.
     */
    private FontMetrics $metrics;

    #[Test]
    public function wrapUsesHyphenatorToFitPartOfWord(): void
    {
        // Arrange
        // maxWidth=5 pt, fontSize=10 → maxUnits = 5*1000/10 = 500
        // space = 100. Current line "aa" (200). Next word "bbb" (300).
        // 200 + 100(space) + 300 = 600 > 500 → doesn't fit.
        // available = 500 - 200 - 100(space) = 200.
        // hyphen '-' = 100. available (200) > hyphenWidth (100).
        // breakWord('bbb') → ['b','bb'] (stub below).
        // Candidate 'b' (100) + 100 = 200 <= 200 → fits → splitAt=0.
        // Line becomes "aa b-", next line starts with "bb".

        $hyphenator = new class implements Hyphenator {
            /** @return array<int, string> */
            public function breakWord(string $word): array
            {
                if ($word === 'bbb') {
                    return ['b', 'bb'];
                }

                return [$word];
            }
        };

        $box = TextBox::create('aa bbb', $this->metrics, fontSize: 10, maxWidth: 5, hyphenator: $hyphenator);

        // Assert
        $lines = $box->getLines();
        self::assertStringEndsWith('-', $lines[0]);
        self::assertSame('bb', $lines[1]);
    }

    #[Test]
    public function wrapFallsBackToNextLineWhenHyphenatedPartDoesNotFit(): void
    {
        // Arrange
        // maxWidth=3 pt, fontSize=10 → maxUnits=300
        // Current line "aa" (200). Next word "bbb" (300).
        // 200+100+300=600 > 300 → doesn't fit.
        // available = 300-200-100 = 0. hyphenWidth=100 > 0 → no split.
        // "aa" is emitted, "bbb" starts the next line.

        $hyphenator = new class implements Hyphenator {
            /** @return array<int, string> */
            public function breakWord(string $word): array
            {
                return ['b', 'b', 'b']; // all parts tiny but available=0
            }
        };

        $box = TextBox::create('aa bbb', $this->metrics, fontSize: 10, maxWidth: 3, hyphenator: $hyphenator);

        $lines = $box->getLines();
        self::assertSame('aa', $lines[0]);
        self::assertSame('bbb', $lines[1]);
    }

    #[Test]
    public function wrapSkipsHyphenationForSinglePartWord(): void
    {
        // Arrange — breakWord returns single element → no split
        $hyphenator = new class implements Hyphenator {
            /** @return array<int, string> */
            public function breakWord(string $word): array
            {
                return [$word];
            }
        };

        // maxWidth=3 → "aa bbb" wraps to ["aa","bbb"]
        $box = TextBox::create('aa bbb', $this->metrics, fontSize: 10, maxWidth: 3, hyphenator: $hyphenator);

        $lines = $box->getLines();
        self::assertSame('aa', $lines[0]);
        self::assertSame('bbb', $lines[1]);
    }

    #[Test]
    public function wrapBreaksAtFirstFittingFragmentWhenNextFragmentExceedsSpace(): void
    {
        // maxWidth=6, fontSize=10 → maxUnits=600, each char = 100 units
        // "aaa" = 300, space=100, "xyz" = 300 → total 700 > 600 — doesn't fit.
        // available = 600 − 300 − 100(space) = 200, hyphenWidth = 100.
        // breakWord('xyz') → ['x','y','z'].
        //   k=0: candidate 'x' (100) + 100 = 200 ≤ 200 → fits; splitAt=0, prefix='x'.
        //   k=1: candidate 'xy' (200) + 100 = 300 > 200 → break (covers the else at line 228).
        // splitAt=0 ≥ 0 → emit "aaa x-", next line starts with "yz".
        $hyphenator = new class implements Hyphenator {
            /** @return array<int, string> */
            public function breakWord(string $word): array
            {
                if ($word === 'xyz') {
                    return ['x', 'y', 'z'];
                }

                return [$word];
            }
        };

        $box = TextBox::create('aaa xyz', $this->metrics, fontSize: 10, maxWidth: 6, hyphenator: $hyphenator);

        $lines = $box->getLines();
        self::assertStringEndsWith('-', $lines[0]);
        self::assertSame('yz', $lines[1]);
    }

    protected function setUp(): void
    {
        $this->metrics = new class implements FontMetrics {
            public function charWidth(int $codePoint): float
            {
                return 100.0;
            }

            public function stringWidth(string $text): float
            {
                return mb_strlen($text, 'UTF-8') * 100.0;
            }
        };
    }
}
