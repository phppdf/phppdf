<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfReal;

use PhpPdf\Object\PdfReal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfReal::class)]
#[CoversMethod(PdfReal::class, 'toPdfString')]
final class ToPdfStringTest extends TestCase
{
    #[Test]
    public function toPdfStringStripsTrailingZeros(): void
    {
        self::assertSame('1.5', (new PdfReal(1.5))->toPdfString());
    }

    #[Test]
    public function toPdfStringStripsTrailingDecimalPoint(): void
    {
        self::assertSame('1', (new PdfReal(1.0))->toPdfString());
    }
}
