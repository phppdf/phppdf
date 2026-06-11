<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'useType1Font')]
final class UseType1FontTest extends TestCase
{
    #[Test]
    public function useType1FontReturnsSelf(): void
    {
        $page = new PdfPageBuilder();

        $result = $page->useType1Font('F1', 'Helvetica');

        self::assertSame($page, $result);
    }
}
