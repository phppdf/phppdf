<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\ShowTextWithPositioning;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'showTextWithPositioning')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(ShowTextWithPositioning::class)]
final class ShowTextWithPositioningTest extends TestCase
{
    #[Test]
    public function showTextWithPositioningReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->showTextWithPositioning(['Hello', -50.0, 'World']));
    }

    #[Test]
    public function showTextWithPositioningAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->showTextWithPositioning(['Hello', -50.0, 'World']);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(ShowTextWithPositioning::class, $ops[0]);
    }

    #[Test]
    public function showTextWithPositioningHandlesFloatElements(): void
    {
        $builder = new PdfContentStreamBuilder();
        // Mixed array: strings are encoded; floats are passed through.
        $builder->showTextWithPositioning([-100.0, 'Text', -50.0]);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(ShowTextWithPositioning::class, $ops[0]);
    }

    #[Test]
    public function showTextWithPositioningHandlesEmptyArray(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->showTextWithPositioning([]);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(ShowTextWithPositioning::class, $ops[0]);
    }
}
