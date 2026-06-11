<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\ShowTextWithPositioning;

use PhpPdf\Content\Operation\ShowTextWithPositioning;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShowTextWithPositioning::class)]
#[CoversMethod(ShowTextWithPositioning::class, 'serialize')]
#[UsesClass(PdfContentStreamSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeMixedStringAndKerning(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfContentStreamSerializer($output);
        $op = new ShowTextWithPositioning(['(Hello)', -100.0]);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("[(Hello) -100] TJ\n", $output->getContent());
    }
}
