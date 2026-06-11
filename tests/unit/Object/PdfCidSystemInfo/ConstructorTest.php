<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfCidSystemInfo;

use PhpPdf\Object\PdfCidSystemInfo;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfCidSystemInfo::class)]
#[CoversMethod(PdfCidSystemInfo::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfString::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesWithDefaults(): void
    {
        // Arrange / Act
        $obj = new PdfCidSystemInfo();

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
