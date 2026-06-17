<?php

declare(strict_types=1);

namespace PhpPdf\Text\TeXHyphenator;

use PhpPdf\Text\TeXHyphenator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use const FILE_IGNORE_NEW_LINES;
use const FILE_SKIP_EMPTY_LINES;

#[CoversClass(TeXHyphenator::class)]
#[CoversMethod(TeXHyphenator::class, 'breakWord')]
final class BreakWordTest extends TestCase
{
    #[Test]
    public function breakWordReturnsSingleFragmentForShortWord(): void
    {
        // Arrange — "hi" is 2 chars, leftMin(2)+rightMin(3)=5 > 2 → no break
        $h = new TeXHyphenator([], leftMin: 2, rightMin: 3);

        // Act
        $parts = $h->breakWord('hi');

        // Assert
        self::assertSame(['hi'], $parts);
    }

    #[Test]
    public function breakWordReturnsSingleFragmentWhenNoPatternsMatch(): void
    {
        // Arrange — no patterns → no break points
        $h = new TeXHyphenator([], leftMin: 1, rightMin: 1);

        // Act
        $parts = $h->breakWord('hello');

        // Assert
        self::assertSame(['hello'], $parts);
    }

    #[Test]
    public function breakWordSplitsWordAtOddWeightPositions(): void
    {
        // Arrange — load the bundled English patterns from the .tex file
        $h = new TeXHyphenator(
            file(
                __DIR__ . '/../../../../resources/hyphenation/en-US.tex',
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES,
            ) ?: [],
        );

        // Act — "hyphenation" is the classic example
        $parts = $h->breakWord('hyphenation');

        // Assert — at least 2 fragments
        self::assertGreaterThan(1, count($parts));
        // Reassembled word should equal original (lowercased)
        self::assertSame('hyphenation', implode('', $parts));
    }

    #[Test]
    public function breakWordRespectsLeftMin(): void
    {
        // Arrange — leftMin=10 prevents breaks for short words
        $h = new TeXHyphenator(['he3l'], leftMin: 10, rightMin: 1);

        // Act
        $parts = $h->breakWord('hello');

        // Assert — word is shorter than leftMin+rightMin → no break
        self::assertSame(['hello'], $parts);
    }

    #[Test]
    public function breakWordConvertsToLowercase(): void
    {
        // Arrange — breakWord lowercases input before matching
        $h = new TeXHyphenator(
            file(
                __DIR__ . '/../../../../resources/hyphenation/en-US.tex',
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES,
            ) ?: [],
        );

        // Act
        $lower = $h->breakWord('hyphenation');
        $upper = $h->breakWord('HYPHENATION');

        // Assert — same result regardless of case
        self::assertSame($lower, $upper);
    }

    #[Test]
    public function breakWordWithCustomPatternAndMinimums(): void
    {
        // Arrange — explicit pattern "a1b" puts weight 1 (odd) at position 1 in 'ab'
        // Word 'abc' with leftMin=1, rightMin=1: break after 'a'
        $h = new TeXHyphenator(['a1b'], leftMin: 1, rightMin: 1);

        // Act
        $parts = $h->breakWord('abc');

        // Assert — 'a' | 'bc'
        self::assertSame(['a', 'bc'], $parts);
    }
}
