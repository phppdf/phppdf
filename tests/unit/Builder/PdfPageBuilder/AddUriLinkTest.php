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
#[CoversMethod(PdfPageBuilder::class, 'addUriLink')]
#[UsesClass(PdfLinkSpec::class)]
final class AddUriLinkTest extends TestCase
{
    #[Test]
    public function addUriLinkReturnsSelf(): void
    {
        $page = new PdfPageBuilder();

        $result = $page->addUriLink(50, 700, 100, 20, 'https://example.com');

        self::assertSame($page, $result);
    }
}
