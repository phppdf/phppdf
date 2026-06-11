<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfOutline;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfOutline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfOutline::class)]
#[CoversMethod(PdfOutline::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange / Act
        $obj = new PdfOutline();

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
