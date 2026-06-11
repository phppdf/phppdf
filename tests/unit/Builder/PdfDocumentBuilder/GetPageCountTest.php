<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'getPageCount')]
#[UsesClass(PdfPageBuilder::class)]
final class GetPageCountTest extends TestCase
{
    #[Test]
    public function getPageCountIsZeroByDefault(): void
    {
        // Arrange / Act
        $builder = new PdfDocumentBuilder();

        // Assert
        self::assertSame(0, $builder->getPageCount());
    }

    #[Test]
    public function getPageCountIncreasesWithEachPageAdded(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $builder->page(static fn () => null);
        $builder->page(static fn () => null);

        // Assert
        self::assertSame(2, $builder->getPageCount());
    }
}
