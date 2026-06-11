<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetLineJoin;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setLineJoin')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetLineJoin::class)]
final class SetLineJoinTest extends TestCase
{
    #[Test]
    public function setLineJoinReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setLineJoin(0));
    }

    #[Test]
    public function setLineJoinAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setLineJoin(0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetLineJoin::class, $ops[0]);
    }
}
