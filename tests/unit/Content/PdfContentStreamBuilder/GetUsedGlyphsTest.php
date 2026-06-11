<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\PdfContentStreamBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'getUsedGlyphs')]
final class GetUsedGlyphsTest extends TestCase
{
    #[Test]
    public function getUsedGlyphsReturnsEmptyByDefault(): void
    {
        $builder = new PdfContentStreamBuilder();

        self::assertSame([], $builder->getUsedGlyphs());
    }
}
