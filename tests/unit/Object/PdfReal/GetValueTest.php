<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfReal;

use PhpPdf\Object\PdfReal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfReal::class)]
#[CoversMethod(PdfReal::class, 'getValue')]
final class GetValueTest extends TestCase
{
    #[Test]
    public function getValueReturnsStoredFloat(): void
    {
        self::assertSame(3.14, (new PdfReal(3.14))->getValue());
    }
}
