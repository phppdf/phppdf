<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfOutlineBuilder;

use PhpPdf\Builder\PdfOutlineBuilder;
use PhpPdf\Builder\PdfOutlineItemSpec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfOutlineBuilder::class)]
#[CoversMethod(PdfOutlineBuilder::class, 'getItems')]
#[UsesClass(PdfOutlineItemSpec::class)]
final class GetItemsTest extends TestCase
{
    #[Test]
    public function getItemsReturnsEmptyArrayByDefault(): void
    {
        // Arrange / Act
        $builder = new PdfOutlineBuilder();

        // Assert
        self::assertSame([], $builder->getItems());
    }

    #[Test]
    public function getItemsReturnsAllAddedItems(): void
    {
        // Arrange
        $builder = new PdfOutlineBuilder();
        $builder->item('First', 0);
        $builder->item('Second', 1);

        // Act
        $items = $builder->getItems();

        // Assert
        self::assertCount(2, $items);
    }
}
