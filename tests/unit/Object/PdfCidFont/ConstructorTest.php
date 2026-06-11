<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfCidFont;

use PhpPdf\Object\PdfCidFont;
use PhpPdf\Object\PdfCidSystemInfo;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfCidFont::class)]
#[CoversMethod(PdfCidFont::class, '__construct')]
#[UsesClass(PdfCidSystemInfo::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfString::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange
        $systemInfo = new PdfCidSystemInfo();
        $fontDescRef = new PdfIndirectReference(5, 0);

        // Act
        $obj = new PdfCidFont('MyFont', $systemInfo, $fontDescRef);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
