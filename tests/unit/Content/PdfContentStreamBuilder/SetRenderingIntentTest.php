<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetRenderingIntent;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setRenderingIntent')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetRenderingIntent::class)]
final class SetRenderingIntentTest extends TestCase
{
    #[Test]
    public function setRenderingIntentReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setRenderingIntent('RelativeColorimetric'));
    }

    #[Test]
    public function setRenderingIntentAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setRenderingIntent('RelativeColorimetric');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetRenderingIntent::class, $ops[0]);
    }
}
