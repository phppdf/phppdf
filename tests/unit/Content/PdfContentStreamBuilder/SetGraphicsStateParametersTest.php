<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetGraphicsStateParameters;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setGraphicsStateParameters')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetGraphicsStateParameters::class)]
final class SetGraphicsStateParametersTest extends TestCase
{
    #[Test]
    public function setGraphicsStateParametersReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setGraphicsStateParameters('GS1'));
    }

    #[Test]
    public function setGraphicsStateParametersAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setGraphicsStateParameters('GS1');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetGraphicsStateParameters::class, $ops[0]);
    }
}
