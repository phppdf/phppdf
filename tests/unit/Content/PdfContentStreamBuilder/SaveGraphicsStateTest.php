<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SaveGraphicsState;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'saveGraphicsState')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SaveGraphicsState::class)]
final class SaveGraphicsStateTest extends TestCase
{
    #[Test]
    public function saveGraphicsStateReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->saveGraphicsState());
    }

    #[Test]
    public function saveGraphicsStateAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->saveGraphicsState();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SaveGraphicsState::class, $ops[0]);
    }
}
