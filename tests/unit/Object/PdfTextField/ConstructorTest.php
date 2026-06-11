<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfTextField;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfRectangle;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfTextField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTextField::class)]
#[CoversMethod(PdfTextField::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfRectangle::class)]
#[UsesClass(PdfString::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $rect = new PdfRectangle(0, 0, 200, 20);

        // Act
        $obj = new PdfTextField('email', $rect);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
