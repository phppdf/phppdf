<?php

declare(strict_types=1);

namespace PhpPdf\Output\PdfStreamOutput;

use PhpPdf\Output\PdfStreamOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfStreamOutput::class)]
#[CoversMethod(PdfStreamOutput::class, 'write')]
final class WriteTest extends TestCase
{
    #[Test]
    public function writeAppendsToStream(): void
    {
        // Arrange
        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        $output = new PdfStreamOutput($stream);

        // Act
        $output->write('Test data');

        // Assert
        rewind($stream);
        self::assertSame('Test data', stream_get_contents($stream));
        fclose($stream);
    }
}
