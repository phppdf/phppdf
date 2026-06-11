<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfContentStreamData;

use PhpPdf\Object\PdfContentStream;
use PhpPdf\Object\PdfContentStreamData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamData::class)]
#[CoversMethod(PdfContentStreamData::class, 'getContent')]
#[UsesClass(PdfContentStream::class)]
final class GetContentTest extends TestCase
{
    #[Test]
    public function getContentReturnsStoredContentStream(): void
    {
        // Arrange
        $stream = new PdfContentStream([]);
        $data = new PdfContentStreamData($stream);

        // Act / Assert
        self::assertSame($stream, $data->getContent());
    }
}
