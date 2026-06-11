<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\BeginCompatibilitySection;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'beginCompatibilitySection')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(BeginCompatibilitySection::class)]
final class BeginCompatibilitySectionTest extends TestCase
{
    #[Test]
    public function beginCompatibilitySectionReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->beginCompatibilitySection());
    }

    #[Test]
    public function beginCompatibilitySectionAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->beginCompatibilitySection();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(BeginCompatibilitySection::class, $ops[0]);
    }
}
