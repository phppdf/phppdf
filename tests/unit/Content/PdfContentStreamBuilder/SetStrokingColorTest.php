<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetStrokingColor;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setStrokingColor')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetStrokingColor::class)]
final class SetStrokingColorTest extends TestCase
{
    #[Test]
    public function setStrokingColorReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setStrokingColor(1.0, 0.0, 0.0));
    }

    #[Test]
    public function setStrokingColorAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setStrokingColor(1.0, 0.0, 0.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetStrokingColor::class, $ops[0]);
    }
}
