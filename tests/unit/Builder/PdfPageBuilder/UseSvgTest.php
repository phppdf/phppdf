<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Svg\SvgDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'useSvg')]
#[UsesClass(SvgDocument::class)]
final class UseSvgTest extends TestCase
{
    #[Test]
    public function useSvgReturnsSelf(): void
    {
        $page = new PdfPageBuilder();
        $svg = SvgDocument::fromString('<svg width="100" height="50" xmlns="http://www.w3.org/2000/svg"/>');

        $result = $page->useSvg('Logo', $svg);

        self::assertSame($page, $result);
    }
}
