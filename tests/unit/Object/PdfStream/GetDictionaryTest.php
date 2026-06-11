<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfStream;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfStream::class)]
#[CoversMethod(PdfStream::class, 'getDictionary')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfRawStreamData::class)]
final class GetDictionaryTest extends TestCase
{
    #[Test]
    public function getDictionaryReturnsConstructorDictionary(): void
    {
        // Arrange
        $dict = new PdfDictionary([]);
        $stream = new PdfStream($dict, new PdfRawStreamData(''));

        // Act / Assert
        self::assertSame($dict, $stream->getDictionary());
    }
}
