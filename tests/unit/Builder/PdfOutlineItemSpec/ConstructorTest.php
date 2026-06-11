<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfOutlineItemSpec;

use PhpPdf\Builder\PdfOutlineBuilder;
use PhpPdf\Builder\PdfOutlineItemSpec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfOutlineItemSpec::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorStoresTitle(): void
    {
        // Arrange / Act
        $spec = new PdfOutlineItemSpec('Introduction', 0, new PdfOutlineBuilder());

        // Assert
        self::assertSame('Introduction', $spec->title);
    }

    #[Test]
    public function constructorStoresPageIndex(): void
    {
        // Arrange / Act
        $spec = new PdfOutlineItemSpec('Chapter 1', 3, new PdfOutlineBuilder());

        // Assert
        self::assertSame(3, $spec->pageIndex);
    }

    #[Test]
    public function constructorStoresChildrenBuilder(): void
    {
        // Arrange
        $children = new PdfOutlineBuilder();

        // Act
        $spec = new PdfOutlineItemSpec('Chapter 1', 0, $children);

        // Assert
        self::assertSame($children, $spec->children);
    }
}
