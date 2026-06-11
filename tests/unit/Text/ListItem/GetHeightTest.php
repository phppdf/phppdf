<?php

declare(strict_types=1);

namespace PhpPdf\Text\ListItem;

use PhpPdf\Text\ListItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ListItem::class)]
#[CoversMethod(ListItem::class, 'getHeight')]
final class GetHeightTest extends TestCase
{
    #[Test]
    public function getHeightIsLineCountTimesLineHeight(): void
    {
        // Arrange — 3 lines, lineHeight = 14
        $item = new ListItem('•', ['Line one', 'Line two', 'Line three'], 14.0);

        // Act / Assert
        self::assertEqualsWithDelta(42.0, $item->getHeight(), 0.001);
    }

    #[Test]
    public function getHeightIsZeroForNoLines(): void
    {
        $item = new ListItem('•', [], 14.0);

        self::assertSame(0.0, $item->getHeight());
    }
}
