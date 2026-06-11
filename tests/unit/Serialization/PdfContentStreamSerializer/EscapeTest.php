<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfContentStreamSerializer;

use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamSerializer::class)]
#[CoversMethod(PdfContentStreamSerializer::class, 'escape')]
#[UsesClass(PdfMemoryOutput::class)]
final class EscapeTest extends TestCase
{
    #[Test]
    public function escapeReplacesBackslash(): void
    {
        $s = new PdfContentStreamSerializer(new PdfMemoryOutput());
        self::assertSame('a\\\\b', $s->escape('a\\b'));
    }

    #[Test]
    public function escapeReplacesOpenParen(): void
    {
        $s = new PdfContentStreamSerializer(new PdfMemoryOutput());
        self::assertSame('\\(', $s->escape('('));
    }

    #[Test]
    public function escapeReplacesCloseParen(): void
    {
        $s = new PdfContentStreamSerializer(new PdfMemoryOutput());
        self::assertSame('\\)', $s->escape(')'));
    }
}
