<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfRawObject;

use PhpPdf\Object\PdfRawObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfRawObject::class)]
#[CoversMethod(PdfRawObject::class, 'getValue')]
final class GetValueTest extends TestCase
{
    #[Test]
    public function getValueReturnsRawContent(): void
    {
        self::assertSame('raw content', (new PdfRawObject('raw content'))->getValue());
    }
}
