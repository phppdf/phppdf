<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\AppendCubicBezier;

use PhpPdf\Content\Operation\AppendCubicBezier;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppendCubicBezier::class)]
#[CoversMethod(AppendCubicBezier::class, 'serialize')]
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
        $op = new AppendCubicBezier(1.0, 2.0, 3.0, 4.0, 5.0, 6.0);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("1 2 3 4 5 6 c
", $output->getContent());
    }
}
