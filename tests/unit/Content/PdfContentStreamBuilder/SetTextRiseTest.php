<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetTextRise;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setTextRise')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetTextRise::class)]
final class SetTextRiseTest extends TestCase
{
    #[Test]
    public function setTextRiseReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setTextRise(3.0));
    }

    #[Test]
    public function setTextRiseAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setTextRise(3.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetTextRise::class, $ops[0]);
    }
}
