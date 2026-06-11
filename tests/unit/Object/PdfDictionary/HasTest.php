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
#[CoversMethod(PdfDictionary::class, 'has')]
#[UsesClass(PdfName::class)]
final class HasTest extends TestCase
{
    #[Test]
    public function hasTrueForExistingKey(): void
    {
        $dict = new PdfDictionary(['Type' => new PdfName('Page')]);
        self::assertTrue($dict->has('Type'));
    }

    #[Test]
    public function hasFalseForMissingKey(): void
    {
        self::assertFalse((new PdfDictionary())->has('Missing'));
    }
}
