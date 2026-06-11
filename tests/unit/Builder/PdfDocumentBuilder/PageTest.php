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
#[CoversMethod(PdfDocumentBuilder::class, 'page')]
#[UsesClass(PdfPageBuilder::class)]
final class PageTest extends TestCase
{
    #[Test]
    public function pageReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->page(static fn () => null);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function pageIncreasesPageCount(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $builder->page(static fn () => null);

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }
}
