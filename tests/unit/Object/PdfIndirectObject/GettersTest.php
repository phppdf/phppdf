<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfIndirectObject;

use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfInteger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfIndirectObject::class)]
#[CoversMethod(PdfIndirectObject::class, 'getObjectNumber')]
#[CoversMethod(PdfIndirectObject::class, 'getGenerationNumber')]
#[CoversMethod(PdfIndirectObject::class, 'getObject')]
#[UsesClass(PdfInteger::class)]
final class GettersTest extends TestCase
{
    #[Test]
    public function gettersReturnConstructorValues(): void
    {
        // Arrange
        $obj = new PdfInteger(42);
        $indirect = new PdfIndirectObject(5, 0, $obj);

        // Act / Assert
        self::assertSame(5, $indirect->getObjectNumber());
        self::assertSame(0, $indirect->getGenerationNumber());
        self::assertSame($obj, $indirect->getObject());
    }
}
