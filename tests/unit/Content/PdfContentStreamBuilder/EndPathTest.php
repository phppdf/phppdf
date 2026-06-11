<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\EndPath;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'endPath')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(EndPath::class)]
final class EndPathTest extends TestCase
{
    #[Test]
    public function endPathReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->endPath());
    }

    #[Test]
    public function endPathAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->endPath();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(EndPath::class, $ops[0]);
    }
}
