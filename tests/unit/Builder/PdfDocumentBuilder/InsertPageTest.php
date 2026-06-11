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
#[CoversMethod(PdfDocumentBuilder::class, 'insertPage')]
#[UsesClass(PdfPageBuilder::class)]
final class InsertPageTest extends TestCase
{
    #[Test]
    public function insertPageReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->insertPage(0, static fn () => null);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function insertPageIncreasesPageCount(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $builder->insertPage(0, static fn () => null);

        // Assert
        self::assertSame(1, $builder->getPageCount());
    }

    #[Test]
    public function insertPageThrowsForNegativeIndex(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Assert / Act
        self::expectException(OutOfBoundsException::class);
        $builder->insertPage(-1, static fn () => null);
    }

    #[Test]
    public function insertPageThrowsWhenBeyondPageCount(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Assert / Act
        self::expectException(OutOfBoundsException::class);
        $builder->insertPage(1, static fn () => null);
    }
}
