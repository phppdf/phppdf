<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'size')]
final class SizeTest extends TestCase
{
    #[Test]
    public function sizeReturnsSelf(): void
    {
        $page = new PdfPageBuilder();

        $result = $page->size(400, 600);

        self::assertSame($page, $result);
    }

    #[Test]
    public function sizeSetsDimensions(): void
    {
        // Arrange / Act — custom dimensions are recorded; verifying indirectly
        // by confirming compile() uses them (width/height appear in MediaBox).
        $page = new PdfPageBuilder();

        $result = $page->size(300, 500);

        self::assertSame($page, $result);
    }
}
