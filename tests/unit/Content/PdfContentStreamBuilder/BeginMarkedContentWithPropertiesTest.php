<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\BeginMarkedContentWithProperties;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'beginMarkedContentWithProperties')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(BeginMarkedContentWithProperties::class)]
final class BeginMarkedContentWithPropertiesTest extends TestCase
{
    #[Test]
    public function beginMarkedContentWithPropertiesReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->beginMarkedContentWithProperties('Span', 'Props'));
    }

    #[Test]
    public function beginMarkedContentWithPropertiesAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->beginMarkedContentWithProperties('Span', 'Props');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(BeginMarkedContentWithProperties::class, $ops[0]);
    }
}
