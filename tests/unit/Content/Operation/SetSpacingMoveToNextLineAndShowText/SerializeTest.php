<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetSpacingMoveToNextLineAndShowText;

use PhpPdf\Content\Operation\SetSpacingMoveToNextLineAndShowText;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetSpacingMoveToNextLineAndShowText::class)]
#[CoversMethod(SetSpacingMoveToNextLineAndShowText::class, 'serialize')]
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
        $op = new SetSpacingMoveToNextLineAndShowText(2.0, 0.0, '(Hello)');

        // Act
        $op->serialize($serializer);

        // Assert
        self::assertSame("2 0 (Hello) \"
", $output->getContent());
    }
}
