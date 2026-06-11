<?php

declare(strict_types=1);

namespace PhpPdf\Output\PdfMemoryOutput;

use PhpPdf\Output\PdfMemoryOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfMemoryOutput::class)]
#[CoversMethod(PdfMemoryOutput::class, 'write')]
final class WriteTest extends TestCase
{
    #[Test]
    public function writeAccumulatesContent(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();

        // Act
        $output->write('hello');
        $output->write(' world');

        // Assert
        self::assertSame('hello world', $output->getContent());
    }
}
