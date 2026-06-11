<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetNonStrokingColor;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setNonStrokingColor')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetNonStrokingColor::class)]
final class SetNonStrokingColorTest extends TestCase
{
    #[Test]
    public function setNonStrokingColorReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setNonStrokingColor(0.5));
    }

    #[Test]
    public function setNonStrokingColorAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setNonStrokingColor(0.5);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetNonStrokingColor::class, $ops[0]);
    }
}
