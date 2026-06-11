<?php

declare(strict_types=1);

namespace PhpPdf\Text\Text\RichTextBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\Hyphenator;
use PhpPdf\Text\RichTextBox;
use PhpPdf\Text\TextSpan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RichTextBox::class)]
#[CoversMethod(RichTextBox::class, 'create')]
#[UsesClass(TextSpan::class)]
final class WrapWithHyphenatorTest extends TestCase
{
    /**
     * Flat metrics: every character is exactly 100 glyph units wide.
     * At fontSize=10 pt: 1 char = 100/1000×10 = 1 pt.
     */
    private FontMetrics $metrics;

    #[Test]
    public function wrapUsesHyphenatorToFitPartOfWordInSingleSpan(): void
    {
        // Arrange
        // maxWidth=5 pt, fontSize=10 → 1 char = 1 pt.
        // "aa" (2 pt) on the line; next word "bbb" (3 pt).
        // 2 + 1(space) + 3 = 6 > 5 → doesn't fit.
        // available = 5 − 2 − 1(space) = 2 pt; hyphen '-' = 1 pt.
        // breakWord('bbb') → ['b','bb']: 'b'(1)+1 = 2 ≤ 2 → fits → line gets "aa b-".
        // Next line: "bb".
        $hyphenator = new class implements Hyphenator {
            /** @return array<int, string> */
            public function breakWord(string $word): array
            {
                return $word === 'bbb'
                    ? ['b', 'bb']
                    : [$word];
            }
        };

        $box = RichTextBox::create(
            spans: [TextSpan::create('aa bbb', 'F1', 10, $this->metrics)],
            maxWidth: 5.0,
            hyphenator: $hyphenator,
        );

        // Act
        $lines = $box->getLines();

        // Assert
        self::assertCount(2, $lines);
        // First line ends with a hyphen
        self::assertStringEndsWith('-', $lines[0][0]->getText());
        // Second line contains the remainder
        self::assertSame('bb', $lines[1][0]->getText());
    }

    #[Test]
    public function wrapHyphenatesWordAcrossAdjacentSpansOfDifferentFonts(): void // phpcs:ignore
    {
        // Arrange — "aa" in F1 (2 pt) then "bbb" in F2 (3 pt).
        // Same arithmetic as above; hyphen is measured in F2's metrics.
        $metrics2 = $this->metrics; // same flat metrics, different font name
        $hyphenator = new class implements Hyphenator {
            /** @return array<int, string> */
            public function breakWord(string $word): array
            {
                return $word === 'bbb'
                    ? ['b', 'bb']
                    : [$word];
            }
        };

        $box = RichTextBox::create(
            spans: [
                TextSpan::create('aa', 'F1', 10, $this->metrics),
                TextSpan::create('bbb', 'F2', 10, $metrics2),
            ],
            maxWidth: 5.0,
            hyphenator: $hyphenator,
        );

        // Act
        $lines = $box->getLines();

        // Assert — first line has two spans (F1 "aa " and F2 "b-"), second has F2 "bb"
        self::assertCount(2, $lines);
        $lastSpanLine0 = end($lines[0]);
        self::assertNotFalse($lastSpanLine0);
        self::assertStringEndsWith('-', $lastSpanLine0->getText());
        self::assertSame('bb', $lines[1][0]->getText());
    }

    // =========================================================================
    // No split when available space is too small for even the hyphen
    // =========================================================================

    #[Test]
    public function wrapFallsBackToNextLineWhenAvailableSpaceIsSmallerThanHyphen(): void
    {
        // Arrange
        // maxWidth=3 pt; "aa"(2) on line; next "bbb"(3).
        // available = 3 − 2 − 1(space) = 0 < 1(hyphen) → no split.
        $hyphenator = new class implements Hyphenator {
            /** @return array<int, string> */
            public function breakWord(string $word): array
            {
                return ['b', 'b', 'b'];
            }
        };

        $box = RichTextBox::create(
            spans: [TextSpan::create('aa bbb', 'F1', 10, $this->metrics)],
            maxWidth: 3.0,
            hyphenator: $hyphenator,
        );

        $lines = $box->getLines();

        self::assertSame('aa', $lines[0][0]->getText());
        self::assertSame('bbb', $lines[1][0]->getText());
    }

    // =========================================================================
    // No split when hyphenator returns a single part
    // =========================================================================

    #[Test]
    public function wrapSkipsHyphenationWhenBreakWordReturnsSinglePart(): void
    {
        $hyphenator = new class implements Hyphenator {
            /** @return array<int, string> */
            public function breakWord(string $word): array
            {
                return [$word];
            }
        };

        $box = RichTextBox::create(
            spans: [TextSpan::create('aa bbb', 'F1', 10, $this->metrics)],
            maxWidth: 3.0,
            hyphenator: $hyphenator,
        );

        $lines = $box->getLines();

        self::assertSame('aa', $lines[0][0]->getText());
        self::assertSame('bbb', $lines[1][0]->getText());
    }

    // =========================================================================
    // Only the first fitting fragment is used
    // =========================================================================

    #[Test]
    public function wrapBreaksAtFirstFittingFragmentWhenNextExceedsAvailableSpace(): void
    {
        // Arrange
        // maxWidth=6 pt; "aaa"(3) on line; next "xyz"(3).
        // available = 6 − 3 − 1(space) = 2 pt; hyphen = 1 pt.
        // breakWord('xyz') → ['x','y','z']:
        //   'x'(1)+1 = 2 ≤ 2 → fitted = 'x'
        //   'xy'(2)+1 = 3 > 2 → stop
        // Line: "aaa x-", next: "yz".
        $hyphenator = new class implements Hyphenator {
            /** @return array<int, string> */
            public function breakWord(string $word): array
            {
                return $word === 'xyz'
                    ? ['x', 'y', 'z']
                    : [$word];
            }
        };

        $box = RichTextBox::create(
            spans: [TextSpan::create('aaa xyz', 'F1', 10, $this->metrics)],
            maxWidth: 6.0,
            hyphenator: $hyphenator,
        );

        $lines = $box->getLines();

        self::assertStringEndsWith('-', $lines[0][0]->getText());
        self::assertSame('yz', $lines[1][0]->getText());
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
