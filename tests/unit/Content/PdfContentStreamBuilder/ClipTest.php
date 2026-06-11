<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetClippingPath;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'clip')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetClippingPath::class)]
final class ClipTest extends TestCase
{
    #[Test]
    public function clipReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->clip());
    }

    #[Test]
    public function clipAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->clip();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetClippingPath::class, $ops[0]);
    }
}
