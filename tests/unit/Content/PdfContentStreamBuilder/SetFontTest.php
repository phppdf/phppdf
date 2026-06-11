<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetFont;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setFont')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetFont::class)]
final class SetFontTest extends TestCase
{
    #[Test]
    public function setFontReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setFont('F1', 12.0));
    }

    #[Test]
    public function setFontAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setFont('F1', 12.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetFont::class, $ops[0]);
    }
}
