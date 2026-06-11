<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetNonStrokingColor;

use PhpPdf\Content\Operation\SetNonStrokingColor;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetNonStrokingColor::class)]
#[CoversMethod(SetNonStrokingColor::class, 'serialize')]
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
        $op = new SetNonStrokingColor(0.5, 0.5);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("0.5 0.5 sc
", $output->getContent());
    }
}
