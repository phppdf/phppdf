<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfRectangle;

use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfRectangle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfRectangle::class)]
#[UsesClass(PdfInteger::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorStoresFourIntegerItems(): void
    {
        // Arrange / Act
        $rect = new PdfRectangle(0, 0, 595, 842);

        // Assert
        $items = $rect->getItems();
        self::assertCount(4, $items);
        self::assertInstanceOf(PdfInteger::class, $items[0]);
        self::assertInstanceOf(PdfInteger::class, $items[2]);
        self::assertInstanceOf(PdfInteger::class, $items[3]);
        self::assertSame(0, $items[0]->getValue());
        self::assertSame(595, $items[2]->getValue());
        self::assertSame(842, $items[3]->getValue());
    }
}
