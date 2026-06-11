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
#[CoversMethod(PdfDictionary::class, 'getEntries')]
#[UsesClass(PdfName::class)]
final class GetEntriesTest extends TestCase
{
    #[Test]
    public function getEntriesReturnsAllEntries(): void
    {
        // Arrange
        $entries = ['Type' => new PdfName('Page')];
        $dict = new PdfDictionary($entries);

        // Act / Assert
        self::assertSame($entries, $dict->getEntries());
    }
}
