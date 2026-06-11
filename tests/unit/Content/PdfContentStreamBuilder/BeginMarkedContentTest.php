<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\BeginMarkedContent;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'beginMarkedContent')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(BeginMarkedContent::class)]
final class BeginMarkedContentTest extends TestCase
{
    #[Test]
    public function beginMarkedContentReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->beginMarkedContent('Span'));
    }

    #[Test]
    public function beginMarkedContentAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->beginMarkedContent('Span');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(BeginMarkedContent::class, $ops[0]);
    }
}
