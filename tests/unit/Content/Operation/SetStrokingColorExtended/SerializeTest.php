<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetStrokingColorExtended;

use PhpPdf\Content\Operation\SetStrokingColorExtended;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetStrokingColorExtended::class)]
#[CoversMethod(SetStrokingColorExtended::class, 'serialize')]
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
        $op = new SetStrokingColorExtended([0.5]);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("0.5 SCN\n", $output->getContent());
    }

    #[Test]
    public function serializeWithPatternName(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $serializer = new PdfContentStreamSerializer($output);
        $op = new SetStrokingColorExtended([0.5], 'Pattern1');

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("0.5 /Pattern1 SCN\n", $output->getContent());
    }
}
