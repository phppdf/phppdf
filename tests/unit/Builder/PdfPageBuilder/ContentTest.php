<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Content\PdfContentStreamBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'content')]
final class ContentTest extends TestCase
{
    #[Test]
    public function contentReturnsSelf(): void
    {
        $page = new PdfPageBuilder();

        $result = $page->content(static fn (PdfContentStreamBuilder $s): null => null);

        self::assertSame($page, $result);
    }
}
