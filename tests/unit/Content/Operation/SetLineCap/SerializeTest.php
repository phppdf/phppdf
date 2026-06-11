<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetLineCap;

use PhpPdf\Content\Operation\SetLineCap;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetLineCap::class)]
#[CoversMethod(SetLineCap::class, 'serialize')]
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
        $op = new SetLineCap(0);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("0 J
", $output->getContent());
    }
}
