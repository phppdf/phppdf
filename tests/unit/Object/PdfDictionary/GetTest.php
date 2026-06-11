<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfDictionary;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDictionary::class)]
#[CoversMethod(PdfDictionary::class, 'get')]
#[UsesClass(PdfName::class)]
final class GetTest extends TestCase
{
    #[Test]
    public function getReturnsValueForExistingKey(): void
    {
        // Arrange
        $name = new PdfName('Page');
        $dict = new PdfDictionary(['Type' => $name]);

        // Act / Assert
        self::assertSame($name, $dict->get('Type'));
    }

    #[Test]
    public function getReturnsNullForMissingKey(): void
    {
        self::assertNull((new PdfDictionary())->get('Missing'));
    }
}
