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
#[CoversMethod(PdfDocumentBuilder::class, 'movePage')]
#[UsesClass(PdfPageBuilder::class)]
final class MovePageTest extends TestCase
{
    #[Test]
    public function movePageReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();
        $builder->page(static fn () => null);
        $builder->page(static fn () => null);

        // Act
        $result = $builder->movePage(0, 1);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function movePageThrowsForOutOfRangeSource(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();
        $builder->page(static fn () => null);

        // Assert / Act
        self::expectException(OutOfBoundsException::class);
        $builder->movePage(1, 0);
    }

    #[Test]
    public function movePageThrowsForOutOfRangeTarget(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();
        $builder->page(static fn () => null);

        // Assert / Act
        self::expectException(OutOfBoundsException::class);
        $builder->movePage(0, 1);
    }
}
