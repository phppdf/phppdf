<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetStrokingColorExtended;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setStrokingColorExtended')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetStrokingColorExtended::class)]
final class SetStrokingColorExtendedTest extends TestCase
{
    #[Test]
    public function setStrokingColorExtendedReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setStrokingColorExtended([1.0], null));
    }

    #[Test]
    public function setStrokingColorExtendedAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setStrokingColorExtended([], 'P1');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetStrokingColorExtended::class, $ops[0]);
    }
}
