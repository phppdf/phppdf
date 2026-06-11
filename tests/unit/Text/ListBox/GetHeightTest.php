<?php

declare(strict_types=1);

namespace PhpPdf\Text\ListBox;

use PhpPdf\Font\FontMetrics;
use PhpPdf\Text\ListBox;
use PhpPdf\Text\ListItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ListBox::class)]
#[CoversMethod(ListBox::class, 'getHeight')]
#[UsesClass(ListItem::class)]
final class GetHeightTest extends TestCase
{
    private FontMetrics $metrics;

    #[Test]
    public function getHeightSumsItemHeightsWithItemSpacing(): void
    {
        // Arrange — 2 single-line items at lineHeight=12, itemSpacing=4
        // Total = 12 + 4 + 12 = 28 (spacing only between items, not after last)
        $list = ListBox::bullet(
            items: ['A', 'B'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 200,
            lineHeight: 12,
            itemSpacing: 4,
        );

        // Act / Assert
        self::assertEqualsWithDelta(28.0, $list->getHeight(), 0.001);
    }

    #[Test]
    public function getHeightWithNoItemSpacingIsSumOfItemHeights(): void
    {
        $list = ListBox::bullet(
            items: ['A', 'B', 'C'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 200,
            lineHeight: 12,
        );

        // 3 items × 12 pt = 36 pt
        self::assertEqualsWithDelta(36.0, $list->getHeight(), 0.001);
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
