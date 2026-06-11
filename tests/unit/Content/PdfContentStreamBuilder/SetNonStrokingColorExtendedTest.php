<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetNonStrokingColorExtended;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setNonStrokingColorExtended')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetNonStrokingColorExtended::class)]
final class SetNonStrokingColorExtendedTest extends TestCase
{
    #[Test]
    public function setNonStrokingColorExtendedReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setNonStrokingColorExtended([0.5], null));
    }

    #[Test]
    public function setNonStrokingColorExtendedAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setNonStrokingColorExtended([], 'P2');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetNonStrokingColorExtended::class, $ops[0]);
    }
}
