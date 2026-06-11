<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetDashPattern;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setDashPattern')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetDashPattern::class)]
final class SetDashPatternTest extends TestCase
{
    #[Test]
    public function setDashPatternReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setDashPattern([3.0, 5.0], 0.0));
    }

    #[Test]
    public function setDashPatternAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setDashPattern([3.0, 5.0], 0.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetDashPattern::class, $ops[0]);
    }
}
