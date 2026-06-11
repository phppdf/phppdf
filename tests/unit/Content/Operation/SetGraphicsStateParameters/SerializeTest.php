<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetGraphicsStateParameters;

use PhpPdf\Content\Operation\SetGraphicsStateParameters;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetGraphicsStateParameters::class)]
#[CoversMethod(SetGraphicsStateParameters::class, 'serialize')]
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
        $op = new SetGraphicsStateParameters('GS1');

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("/GS1 gs
", $output->getContent());
    }
}
