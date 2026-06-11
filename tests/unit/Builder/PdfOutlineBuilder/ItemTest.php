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
#[CoversMethod(PdfOutlineBuilder::class, 'item')]
#[UsesClass(PdfOutlineItemSpec::class)]
final class ItemTest extends TestCase
{
    #[Test]
    public function itemReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfOutlineBuilder();

        // Act
        $result = $builder->item('Introduction', 0);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function itemStoresTitleAndPageIndex(): void
    {
        // Arrange
        $builder = new PdfOutlineBuilder();

        // Act
        $builder->item('Chapter 1', 2);

        // Assert
        $items = $builder->getItems();
        self::assertCount(1, $items);
        self::assertSame('Chapter 1', $items[0]->title);
        self::assertSame(2, $items[0]->pageIndex);
    }

    #[Test]
    public function itemAcceptsConfigureCallbackForChildren(): void
    {
        // Arrange
        $builder = new PdfOutlineBuilder();

        // Act
        $builder->item('Chapter', 0, static function (PdfOutlineBuilder $child): void {
            $child->item('Section 1.1', 1);
        });

        // Assert
        $children = $builder->getItems()[0]->children->getItems();
        self::assertCount(1, $children);
        self::assertSame('Section 1.1', $children[0]->title);
    }
}
