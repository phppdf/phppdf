<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\RestoreGraphicsState;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'restoreGraphicsState')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(RestoreGraphicsState::class)]
final class RestoreGraphicsStateTest extends TestCase
{
    #[Test]
    public function restoreGraphicsStateReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->restoreGraphicsState());
    }

    #[Test]
    public function restoreGraphicsStateAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->restoreGraphicsState();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(RestoreGraphicsState::class, $ops[0]);
    }
}
