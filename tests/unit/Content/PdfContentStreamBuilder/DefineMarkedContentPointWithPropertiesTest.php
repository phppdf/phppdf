<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\DefineMarkedContentPointWithProperties;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'defineMarkedContentPointWithProperties')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(DefineMarkedContentPointWithProperties::class)]
final class DefineMarkedContentPointWithPropertiesTest extends TestCase
{
    #[Test]
    public function defineMarkedContentPointWithPropertiesReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->defineMarkedContentPointWithProperties('Note', 'Props'));
    }

    #[Test]
    public function defineMarkedContentPointWithPropertiesAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->defineMarkedContentPointWithProperties('Note', 'Props');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(DefineMarkedContentPointWithProperties::class, $ops[0]);
    }
}
