<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use OutOfBoundsException;
use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'removePage')]
#[UsesClass(PdfPageBuilder::class)]
final class RemovePageTest extends TestCase
{
    #[Test]
    public function removePageReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();
        $builder->page(static fn () => null);

        // Act
        $result = $builder->removePage(0);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function removePageDecreasesPageCount(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();
        $builder->page(static fn () => null);
        $builder->page(static fn () => null);

        // Act
        $builder->removePage(0);

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function removePageThrowsForNegativeIndex(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();
        $builder->page(static fn () => null);

        // Assert / Act
        self::expectException(OutOfBoundsException::class);
        $builder->removePage(-1);
    }

    #[Test]
    public function removePageThrowsWhenIndexEqualsPageCount(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();
        $builder->page(static fn () => null);

        // Assert / Act
        self::expectException(OutOfBoundsException::class);
        $builder->removePage(1);
    }
}
