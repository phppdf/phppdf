<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfSignatureDictionary;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfSignatureDictionary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfSignatureDictionary::class)]
#[CoversMethod(PdfSignatureDictionary::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesWithDefaults(): void
    {
        // Arrange / Act
        $obj = new PdfSignatureDictionary();

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
