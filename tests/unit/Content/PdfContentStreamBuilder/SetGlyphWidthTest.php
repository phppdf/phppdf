<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetGlyphWidth;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setGlyphWidth')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetGlyphWidth::class)]
final class SetGlyphWidthTest extends TestCase
{
    #[Test]
    public function setGlyphWidthReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setGlyphWidth(600.0, 0.0));
    }

    #[Test]
    public function setGlyphWidthAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setGlyphWidth(600.0, 0.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetGlyphWidth::class, $ops[0]);
    }
}
