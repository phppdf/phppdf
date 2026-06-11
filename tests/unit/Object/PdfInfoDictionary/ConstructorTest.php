<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfInfoDictionary;

use PhpPdf\Object\PdfDate;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInfoDictionary;
use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfInfoDictionary::class)]
#[CoversMethod(PdfInfoDictionary::class, '__construct')]
#[UsesClass(PdfDate::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfString::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange / Act
        $obj = new PdfInfoDictionary('My Title', 'Jane Doe');

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
