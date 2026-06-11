<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfLinkSpec;
use PhpPdf\Builder\PdfPageBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'addPageLink')]
#[UsesClass(PdfLinkSpec::class)]
final class AddPageLinkTest extends TestCase
{
    #[Test]
    public function addPageLinkReturnsSelf(): void
    {
        $page = new PdfPageBuilder();

        $result = $page->addPageLink(50, 700, 100, 20, 0);

        self::assertSame($page, $result);
    }
}
