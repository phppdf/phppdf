<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use InvalidArgumentException;
use PhpPdf\Builder\PdfPageBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'rotate')]
final class RotateTest extends TestCase
{
    #[Test]
    public function rotateReturnsSelfForValidDegrees(): void
    {
        $page = new PdfPageBuilder();

        self::assertSame($page, $page->rotate(0));
        self::assertSame($page, $page->rotate(90));
        self::assertSame($page, $page->rotate(180));
        self::assertSame($page, $page->rotate(270));
    }

    #[Test]
    public function rotateThrowsForInvalidDegrees(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rotation must be 0, 90, 180, or 270; got 45.');

        (new PdfPageBuilder())->rotate(45);
    }
}
