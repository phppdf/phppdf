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
#[CoversMethod(ListBox::class, 'numbered')]
#[UsesClass(ListItem::class)]
final class NumberedTest extends TestCase
{
    private FontMetrics $metrics;

    #[Test]
    public function numberedCreatesOneItemPerString(): void
    {
        $list = ListBox::numbered(
            items: ['Alpha', 'Beta', 'Gamma'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 300,
        );

        self::assertCount(3, $list->getItems());
    }

    #[Test]
    public function numberedUsesFormattedMarkers(): void
    {
        $list = ListBox::numbered(
            items: ['Alpha', 'Beta'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 300,
            startAt: 3,
        );

        self::assertSame('3.', $list->getItems()[0]->marker);
        self::assertSame('4.', $list->getItems()[1]->marker);
    }

    #[Test]
    public function numberedAutoComputesIndentFromWidestMarker(): void
    {
        // Arrange — 10 items, widest marker "10." → computed indent
        $items = array_fill(0, 10, 'Item text');

        $list = ListBox::numbered(items: $items, metrics: $this->metrics, fontSize: 10, maxWidth: 300);

        // Assert — indent is positive and auto-computed
        self::assertGreaterThan(0.0, $list->getIndent());
    }

    #[Test]
    public function numberedUsesExplicitIndent(): void
    {
        $list = ListBox::numbered(
            items: ['Item'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 300,
            indent: 40,
        );

        self::assertEqualsWithDelta(40.0, $list->getIndent(), 0.001);
    }

    #[Test]
    public function numberedDefaultsLineHeight(): void
    {
        $list = ListBox::numbered(
            items: ['Item'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 300,
        );

        self::assertEqualsWithDelta(12.0, $list->getLineHeight(), 0.001);
    }

    #[Test]
    public function numberedUsesCustomNumberFormat(): void
    {
        $list = ListBox::numbered(
            items: ['Item'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 300,
            numberFormat: '(%d)',
        );

        self::assertSame('(1)', $list->getItems()[0]->marker);
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
