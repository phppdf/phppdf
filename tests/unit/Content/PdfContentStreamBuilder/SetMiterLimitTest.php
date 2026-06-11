<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetMiterLimit;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setMiterLimit')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetMiterLimit::class)]
final class SetMiterLimitTest extends TestCase
{
    #[Test]
    public function setMiterLimitReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setMiterLimit(10.0));
    }

    #[Test]
    public function setMiterLimitAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setMiterLimit(10.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetMiterLimit::class, $ops[0]);
    }
}
