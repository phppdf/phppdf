<?php

declare(strict_types=1);

namespace PhpPdf\Text\TextBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\TextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextBox::class)]
#[CoversMethod(TextBox::class, 'create')]
final class CreateTest extends TestCase
{
    private FontMetrics $metrics;

    #[Test]
    public function createReturnsTextBox(): void
    {
        // Arrange / Act
        $box = TextBox::create('Hello', $this->metrics, fontSize: 10, maxWidth: 200);

        // Assert
        self::assertInstanceOf(TextBox::class, $box);
    }

    #[Test]
    public function createUsesDefaultLineHeightWhenZero(): void
    {
        // Arrange — lineHeight=0 → defaults to fontSize × 1.2
        $box = TextBox::create('Hello', $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 0);

        // Assert
        self::assertEqualsWithDelta(12.0, $box->getLineHeight(), 0.001);
    }

    #[Test]
    public function createUsesExplicitLineHeight(): void
    {
        $box = TextBox::create('Hello', $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 15);

        self::assertEqualsWithDelta(15.0, $box->getLineHeight(), 0.001);
    }

    #[Test]
    public function createWrapsLongTextIntoMultipleLines(): void
    {
        // Arrange — each char = 500/1000*10 = 5 pt; maxWidth=20 → 4 chars per line
        // "aa bb cc" → lines: "aa", "bb", "cc" (space char fits, words don't chain)
        // word "aa" = 2 chars * 500 = 1000 glyph units; at fontSize=10 → 10pt
        // maxWidth=20 → maxUnits = 20*1000/10 = 2000 units
        // "aa" (1000) + space (500) + "bb" (1000) = 2500 > 2000 → wraps
        $box = TextBox::create('aa bb cc', $this->metrics, fontSize: 10, maxWidth: 20);

        self::assertSame(['aa', 'bb', 'cc'], $box->getLines());
    }

    #[Test]
    public function createPreservesNewlinesAsLineBreaks(): void
    {
        $box = TextBox::create("line1\nline2", $this->metrics, fontSize: 10, maxWidth: 200);

        self::assertSame(['line1', 'line2'], $box->getLines());
    }

    #[Test]
    public function createPreservesBlankLinesFromDoubleNewline(): void
    {
        $box = TextBox::create("para1\n\npara2", $this->metrics, fontSize: 10, maxWidth: 200);

        self::assertSame(['para1', '', 'para2'], $box->getLines());
    }

    #[Test]
    public function createPlacesOversizedWordOnItsOwnLine(): void
    {
        // Arrange — single word wider than maxWidth still goes on one line
        // "ABCDEF" = 6 * 500 = 3000 units; maxUnits at fontSize=10 = 100*1000/10 = 10000
        // actually let me use maxWidth=1 → maxUnits = 100 → word (3000) > max, but still placed
        $box = TextBox::create('ABCDEF', $this->metrics, fontSize: 10, maxWidth: 1);

        self::assertSame(['ABCDEF'], $box->getLines());
    }

    #[Test]
    public function createSkipsParagraphThatYieldsNoWordsAfterSplitting(): void
    {
        // Arrange — a paragraph consisting solely of a form feed (\x0C) is not
        // stripped by rtrim() but is matched by preg_split('/\s+/', ...), so
        // PREG_SPLIT_NO_EMPTY yields an empty $words array. The foreach over
        // $words never runs, leaving $currentLine === '' which is skipped
        // entirely (no line emitted) rather than appended as a blank line.
        $box = TextBox::create("line1\n\x0C\nline2", $this->metrics, fontSize: 10, maxWidth: 200);

        self::assertSame(['line1', 'line2'], $box->getLines());
    }

    protected function setUp(): void
    {
        // Fixed-width stub: every character is 500 glyph units wide
        $this->metrics = new class implements FontMetrics {
            public function charWidth(int $codePoint): float
            {
                return 500.0;
            }

            public function stringWidth(string $text): float
            {
                return mb_strlen($text, 'UTF-8') * 500.0;
            }
        };
    }
}
