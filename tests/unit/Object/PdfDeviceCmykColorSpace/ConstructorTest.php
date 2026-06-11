<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfDeviceCmykColorSpace;

use PhpPdf\Object\PdfDeviceCmykColorSpace;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDeviceCmykColorSpace::class)]
#[CoversMethod(PdfDeviceCmykColorSpace::class, '__construct')]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange / Act
        $obj = new PdfDeviceCmykColorSpace();

        // Assert
        self::assertInstanceOf(PdfName::class, $obj);
        self::assertSame('DeviceCMYK', $obj->getValue());
    }
}
