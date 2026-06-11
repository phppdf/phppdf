<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfNull;

use PhpPdf\Object\PdfNull;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfNull::class)]
#[CoversMethod(PdfNull::class, 'toPdfString')]
final class ToPdfStringTest extends TestCase
{
    #[Test]
    public function toPdfStringReturnsNull(): void
    {
        self::assertSame('null', (new PdfNull())->toPdfString());
    }
}
