<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\CloseSubpath;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'closePath')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(CloseSubpath::class)]
final class ClosePathTest extends TestCase
{
    #[Test]
    public function closePathReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->closePath());
    }

    #[Test]
    public function closePathAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->closePath();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(CloseSubpath::class, $ops[0]);
    }
}
