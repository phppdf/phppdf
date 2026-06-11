<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfArray;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfInteger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfArray::class)]
#[CoversMethod(PdfArray::class, 'getItems')]
#[UsesClass(PdfInteger::class)]
final class GetItemsTest extends TestCase
{
    #[Test]
    public function getItemsReturnsStoredItems(): void
    {
        // Arrange
        $items = [new PdfInteger(1), new PdfInteger(2)];

        // Act / Assert
        self::assertSame($items, (new PdfArray($items))->getItems());
    }

    #[Test]
    public function getItemsReturnsEmptyArray(): void
    {
        self::assertSame([], (new PdfArray([]))->getItems());
    }
}
