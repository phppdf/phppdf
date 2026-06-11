<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetGlyphWidthAndBoundingBox;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setGlyphWidthAndBoundingBox')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetGlyphWidthAndBoundingBox::class)]
final class SetGlyphWidthAndBoundingBoxTest extends TestCase
{
    #[Test]
    public function setGlyphWidthAndBoundingBoxReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setGlyphWidthAndBoundingBox(600.0, 0.0, 0.0, -200.0, 600.0, 800.0));
    }

    #[Test]
    public function setGlyphWidthAndBoundingBoxAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setGlyphWidthAndBoundingBox(600.0, 0.0, 0.0, -200.0, 600.0, 800.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetGlyphWidthAndBoundingBox::class, $ops[0]);
    }
}
