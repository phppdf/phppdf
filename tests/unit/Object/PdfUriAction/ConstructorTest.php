<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfUriAction;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfUriAction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfUriAction::class)]
#[CoversMethod(PdfUriAction::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfString::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesInstance(): void
    {
        // Arrange / Act
        $obj = new PdfUriAction('https://example.com');

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $obj);
    }
}
