<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\EndMarkedContent;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'endMarkedContent')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(EndMarkedContent::class)]
final class EndMarkedContentTest extends TestCase
{
    #[Test]
    public function endMarkedContentReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->endMarkedContent());
    }

    #[Test]
    public function endMarkedContentAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->endMarkedContent();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(EndMarkedContent::class, $ops[0]);
    }
}
