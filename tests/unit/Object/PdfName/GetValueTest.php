<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfName;

use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfName::class)]
#[CoversMethod(PdfName::class, 'getValue')]
final class GetValueTest extends TestCase
{
    #[Test]
    public function getValueReturnsStoredName(): void
    {
        self::assertSame('Font', (new PdfName('Font'))->getValue());
    }
}
