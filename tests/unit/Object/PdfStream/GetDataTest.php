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
#[CoversMethod(PdfStream::class, 'getData')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfRawStreamData::class)]
final class GetDataTest extends TestCase
{
    #[Test]
    public function getDataReturnsConstructorData(): void
    {
        // Arrange
        $data = new PdfRawStreamData('test');
        $stream = new PdfStream(new PdfDictionary([]), $data);

        // Act / Assert
        self::assertSame($data, $stream->getData());
    }
}
