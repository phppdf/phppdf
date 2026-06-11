<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfFontResources;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfFontResources;
use PhpPdf\Object\PdfIndirectReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFontResources::class)]
#[CoversMethod(PdfFontResources::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange / Act
        $obj = new PdfFontResources(['F1' => new PdfIndirectReference(3, 0)]);

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
