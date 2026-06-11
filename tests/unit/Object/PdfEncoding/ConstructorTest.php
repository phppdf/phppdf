<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfEncoding;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfEncoding;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfEncoding::class)]
#[CoversMethod(PdfEncoding::class, '__construct')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesWithDefaults(): void
    {
        // Arrange / Act
        $obj = new PdfEncoding();

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }

    #[Test]
    public function constructorCreatesWithDifferences(): void
    {
        // Arrange / Act
        $obj = new PdfEncoding('WinAnsiEncoding', [161 => 'exclamdown']);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
