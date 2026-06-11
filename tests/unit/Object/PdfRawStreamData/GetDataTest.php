<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfRawStreamData;

use PhpPdf\Object\PdfRawStreamData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfRawStreamData::class)]
#[CoversMethod(PdfRawStreamData::class, 'getData')]
final class GetDataTest extends TestCase
{
    #[Test]
    public function getDataReturnsStoredData(): void
    {
        self::assertSame('raw', (new PdfRawStreamData('raw'))->getData());
    }
}
