<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\ShowCidText;

use PhpPdf\Content\Operation\ShowCidText;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShowCidText::class)]
#[CoversMethod(ShowCidText::class, 'serialize')]
#[UsesClass(PdfContentStreamSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeEncodesGlyphIdsAsHex(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfContentStreamSerializer($output);
        $op = new ShowCidText([0x0041, 0x0042]);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("<00410042> Tj\n", $output->getContent());
    }
}
