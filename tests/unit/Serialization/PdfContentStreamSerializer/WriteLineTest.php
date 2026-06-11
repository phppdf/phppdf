<?php

declare(strict_types=1);

namespace PhpPdf\Serialization\PdfContentStreamSerializer;

use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfContentStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamSerializer::class)]
#[CoversMethod(PdfContentStreamSerializer::class, 'writeLine')]
#[UsesClass(PdfMemoryOutput::class)]
final class WriteLineTest extends TestCase
{
    #[Test]
    public function writeLineAppendsNewline(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $s = new PdfContentStreamSerializer($output);

        // Act
        $s->writeLine('BT');

        // Assert
        self::assertSame("BT\n", $output->getContent());
    }
}
