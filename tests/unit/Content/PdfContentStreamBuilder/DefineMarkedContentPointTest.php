<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\DefineMarkedContentPoint;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'defineMarkedContentPoint')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(DefineMarkedContentPoint::class)]
final class DefineMarkedContentPointTest extends TestCase
{
    #[Test]
    public function defineMarkedContentPointReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->defineMarkedContentPoint('Note'));
    }

    #[Test]
    public function defineMarkedContentPointAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->defineMarkedContentPoint('Note');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(DefineMarkedContentPoint::class, $ops[0]);
    }
}
