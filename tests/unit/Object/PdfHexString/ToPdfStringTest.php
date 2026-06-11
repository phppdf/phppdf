<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfHexString;

use PhpPdf\Object\PdfHexString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfHexString::class)]
#[CoversMethod(PdfHexString::class, 'toPdfString')]
final class ToPdfStringTest extends TestCase
{
    #[Test]
    public function toPdfStringReturnsUppercaseHex(): void
    {
        $binary = "\x48\x65\x6C\x6C\x6F";
        self::assertSame('<48656C6C6F>', (new PdfHexString($binary))->toPdfString());
    }
}
