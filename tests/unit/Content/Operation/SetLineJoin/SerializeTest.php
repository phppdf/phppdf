<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetLineJoin;

use PhpPdf\Content\Operation\SetLineJoin;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetLineJoin::class)]
#[CoversMethod(SetLineJoin::class, 'serialize')]
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
        $op = new SetLineJoin(0);

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("0 j
", $output->getContent());
    }
}
