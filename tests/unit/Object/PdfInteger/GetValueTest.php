<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfInteger;

use PhpPdf\Object\PdfInteger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfInteger::class)]
#[CoversMethod(PdfInteger::class, 'getValue')]
final class GetValueTest extends TestCase
{
    #[Test]
    public function getValueReturnsStoredInteger(): void
    {
        self::assertSame(42, (new PdfInteger(42))->getValue());
    }
}
