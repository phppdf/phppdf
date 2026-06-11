<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'build')]
#[UsesClass(PdfContentStream::class)]
final class BuildTest extends TestCase
{
    #[Test]
    public function buildReturnsContentStream(): void
    {
        $builder = new PdfContentStreamBuilder();

        self::assertInstanceOf(PdfContentStream::class, $builder->build());
    }

    #[Test]
    public function buildReturnsFreshStreamWithNoOperations(): void
    {
        $builder = new PdfContentStreamBuilder();

        self::assertSame([], $builder->build()->getOperations());
    }

    #[Test]
    public function buildCanBeCalledMultipleTimes(): void
    {
        $builder = new PdfContentStreamBuilder();

        $first = $builder->build();
        $second = $builder->build();

        self::assertNotSame($first, $second);
    }
}
