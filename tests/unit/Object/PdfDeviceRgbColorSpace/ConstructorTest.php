<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfDeviceRgbColorSpace;

use PhpPdf\Object\PdfDeviceRgbColorSpace;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDeviceRgbColorSpace::class)]
#[CoversMethod(PdfDeviceRgbColorSpace::class, '__construct')]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange / Act
        $obj = new PdfDeviceRgbColorSpace();

        // Assert
        self::assertInstanceOf(PdfName::class, $obj);
        self::assertSame('DeviceRGB', $obj->getValue());
    }
}
