<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfPageBuilder;

use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfGraphicsStateDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfReal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfPageBuilder::class)]
#[CoversMethod(PdfPageBuilder::class, 'useGraphicsState')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfGraphicsStateDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfReal::class)]
final class UseGraphicsStateTest extends TestCase
{
    #[Test]
    public function useGraphicsStateReturnsSelf(): void
    {
        $page = new PdfPageBuilder();
        $state = new PdfGraphicsStateDictionary(fillAlpha: 0.5);

        $result = $page->useGraphicsState('GS1', $state);

        self::assertSame($page, $result);
    }
}
