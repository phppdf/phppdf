<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\EndCompatibilitySection;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'endCompatibilitySection')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(EndCompatibilitySection::class)]
final class EndCompatibilitySectionTest extends TestCase
{
    #[Test]
    public function endCompatibilitySectionReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->endCompatibilitySection());
    }

    #[Test]
    public function endCompatibilitySectionAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->endCompatibilitySection();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(EndCompatibilitySection::class, $ops[0]);
    }
}
