<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetLineWidth;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setLineWidth')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetLineWidth::class)]
final class SetLineWidthTest extends TestCase
{
    #[Test]
    public function setLineWidthReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setLineWidth(2.0));
    }

    #[Test]
    public function setLineWidthAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setLineWidth(2.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetLineWidth::class, $ops[0]);
    }
}
