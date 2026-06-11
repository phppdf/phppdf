<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\AppendLine;

use PhpPdf\Content\Operation\AppendLine;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppendLine::class)]
#[CoversMethod(AppendLine::class, 'serialize')]
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
        $op = new AppendLine(1.0, 2.0);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("1 2 l
", $output->getContent());
    }
}
