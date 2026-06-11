<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetGlyphWidth;

use PhpPdf\Content\Operation\SetGlyphWidth;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetGlyphWidth::class)]
#[CoversMethod(SetGlyphWidth::class, 'serialize')]
#[UsesClass(PdfContentStreamSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeProducesExpectedOutput(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfContentStreamSerializer($output);
        $op = new SetGlyphWidth(100.0, 0.0);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("100 0 d0
", $output->getContent());
    }
}
