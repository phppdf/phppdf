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
#[CoversMethod(PdfStream::class, '__construct')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfRawStreamData::class)]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorCreatesStream(): void
    {
        // Arrange / Act
        $dict = new PdfDictionary([]);
        $data = new PdfRawStreamData('hello');
        $stream = new PdfStream($dict, $data);

        // Assert
        self::assertInstanceOf(PdfStream::class, $stream);
    }
}
