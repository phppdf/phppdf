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
#[CoversMethod(TextBox::class, 'overflows')]
final class OverflowsTest extends TestCase
{
    private FontMetrics $metrics;

    #[Test]
    public function overflowsReturnsTrueWhenHeightExceedsMax(): void
    {
        // Arrange — 3 lines at lineHeight 12 → height = 36; maxHeight = 20
        $box = TextBox::create("a\nb\nc", $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 12);

        self::assertTrue($box->overflows(20));
    }

    #[Test]
    public function overflowsReturnsFalseWhenHeightFits(): void
    {
        // Arrange — 1 line at lineHeight 12 → height = 12; maxHeight = 50
        $box = TextBox::create('hello', $this->metrics, fontSize: 10, maxWidth: 200, lineHeight: 12);

        self::assertFalse($box->overflows(50));
    }

    protected function setUp(): void
    {
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
