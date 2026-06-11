<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfString;

use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfString::class)]
#[CoversMethod(PdfString::class, 'getValue')]
final class GetValueTest extends TestCase
{
    #[Test]
    public function getValueReturnsStoredString(): void
    {
        self::assertSame('hello', (new PdfString('hello'))->getValue());
    }
}
