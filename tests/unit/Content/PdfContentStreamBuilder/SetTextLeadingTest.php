<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetTextLeading;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setTextLeading')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetTextLeading::class)]
final class SetTextLeadingTest extends TestCase
{
    #[Test]
    public function setTextLeadingReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setTextLeading(14.0));
    }

    #[Test]
    public function setTextLeadingAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setTextLeading(14.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetTextLeading::class, $ops[0]);
    }
}
