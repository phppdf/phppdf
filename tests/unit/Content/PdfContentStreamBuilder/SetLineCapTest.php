<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetLineCap;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setLineCap')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetLineCap::class)]
final class SetLineCapTest extends TestCase
{
    #[Test]
    public function setLineCapReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setLineCap(1));
    }

    #[Test]
    public function setLineCapAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setLineCap(1);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetLineCap::class, $ops[0]);
    }
}
