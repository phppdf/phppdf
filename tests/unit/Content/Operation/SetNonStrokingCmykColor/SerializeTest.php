<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetNonStrokingCmykColor;

use PhpPdf\Content\Operation\SetNonStrokingCmykColor;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetNonStrokingCmykColor::class)]
#[CoversMethod(SetNonStrokingCmykColor::class, 'serialize')]
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
        $op = new SetNonStrokingCmykColor(0.0, 0.0, 0.0, 1.0);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("0 0 0 1 k
", $output->getContent());
    }
}
