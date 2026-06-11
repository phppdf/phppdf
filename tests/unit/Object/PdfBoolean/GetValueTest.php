<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfBoolean;

use PhpPdf\Object\PdfBoolean;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfBoolean::class)]
#[CoversMethod(PdfBoolean::class, 'getValue')]
final class GetValueTest extends TestCase
{
    #[Test]
    public function getValueReturnsTrue(): void
    {
        self::assertTrue((new PdfBoolean(true))->getValue());
    }

    #[Test]
    public function getValueReturnsFalse(): void
    {
        self::assertFalse((new PdfBoolean(false))->getValue());
    }
}
