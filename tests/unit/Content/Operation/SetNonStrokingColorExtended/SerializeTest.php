<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetNonStrokingColorExtended;

use PhpPdf\Content\Operation\SetNonStrokingColorExtended;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetNonStrokingColorExtended::class)]
#[CoversMethod(SetNonStrokingColorExtended::class, 'serialize')]
#[UsesClass(PdfContentStreamSerializer::class)]
#[UsesClass(PdfMemoryOutput::class)]
final class SerializeTest extends TestCase
{
    #[Test]
    public function serializeWithComponentsOnly(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfContentStreamSerializer($output);
        $op = new SetNonStrokingColorExtended([0.5]);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("0.5 scn\n", $output->getContent());
    }

    #[Test]
    public function serializeWithPatternName(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfContentStreamSerializer($output);
        $op = new SetNonStrokingColorExtended([0.5], 'Pattern1');

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("0.5 /Pattern1 scn\n", $output->getContent());
    }
}
