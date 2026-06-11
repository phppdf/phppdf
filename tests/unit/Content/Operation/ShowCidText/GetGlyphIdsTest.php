<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\ShowCidText;

use PhpPdf\Content\Operation\ShowCidText;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShowCidText::class)]
#[CoversMethod(ShowCidText::class, 'getGlyphIds')]
final class GetGlyphIdsTest extends TestCase
{
    #[Test]
    public function getGlyphIdsReturnsStoredIds(): void
    {
        // Arrange
        $ids = [0x0041, 0x0042, 0x0043];
        $op = new ShowCidText($ids);

        // Act / Assert
        self::assertSame($ids, $op->getGlyphIds());
    }
}
