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
#[CoversMethod(ListBox::class, 'bullet')]
#[UsesClass(ListItem::class)]
final class BulletTest extends TestCase
{
    private FontMetrics $metrics;

    #[Test]
    public function bulletCreatesOneItemPerString(): void
    {
        // Arrange / Act
        $list = ListBox::bullet(
            items: ['First', 'Second', 'Third'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 200,
        );

        // Assert
        self::assertCount(3, $list->getItems());
    }

    #[Test]
    public function bulletUsesBulletCharacterAsMarker(): void
    {
        $list = ListBox::bullet(
            items: ['Item'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 200,
            bullet: '→',
        );

        self::assertSame('→', $list->getItems()[0]->marker);
    }

    #[Test]
    public function bulletDefaultsLineHeightToFontSizeTimes1Point2(): void
    {
        $list = ListBox::bullet(
            items: ['Item'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 200,
        );

        self::assertEqualsWithDelta(12.0, $list->getLineHeight(), 0.001);
    }

    #[Test]
    public function bulletDefaultsIndentToFontSizeTimes2(): void
    {
        $list = ListBox::bullet(
            items: ['Item'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 200,
        );

        self::assertEqualsWithDelta(20.0, $list->getIndent(), 0.001);
    }

    #[Test]
    public function bulletUsesExplicitLineHeightAndIndent(): void
    {
        $list = ListBox::bullet(
            items: ['Item'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 200,
            lineHeight: 15,
            indent: 30,
        );

        self::assertEqualsWithDelta(15.0, $list->getLineHeight(), 0.001);
        self::assertEqualsWithDelta(30.0, $list->getIndent(), 0.001);
    }

    #[Test]
    public function bulletAccessorsReturnConfiguredValues(): void
    {
        $list = ListBox::bullet(
            items: ['Item'],
            metrics: $this->metrics,
            fontSize: 11,
            maxWidth: 300,
            lineHeight: 14,
            itemSpacing: 4,
        );

        self::assertEqualsWithDelta(11.0, $list->getFontSize(), 0.001);
        self::assertEqualsWithDelta(4.0, $list->getItemSpacing(), 0.001);
    }

    #[Test]
    public function bulletWrapsLongItemsAcrossMultipleLines(): void
    {
        // Arrange — mock gives 500 units/char, fontSize=10 → maxUnits=5000/col.
        // indent = fontSize*2 = 20; textWidth = 50 − 20 = 30 pt
        // textWidth in units = 30*1000/10 = 3000.
        // "Hello" = 5*500=2500, "World" = 5*500=2500.
        // 2500 + 500(space) + 2500 = 5500 > 3000 → else branch in wrap().
        $list = ListBox::bullet(
            items: ['Hello World'],
            metrics: $this->metrics,
            fontSize: 10,
            maxWidth: 50,
        );

        // Assert — item is wrapped into two lines
        self::assertCount(2, $list->getItems()[0]->lines);
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
