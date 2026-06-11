<?php

declare(strict_types=1);

namespace PhpPdf\Output\PdfStreamOutput;

use PhpPdf\Output\PdfStreamOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfStreamOutput::class)]
#[CoversMethod(PdfStreamOutput::class, 'position')]
final class PositionTest extends TestCase
{
    #[Test]
    public function positionReturnsZeroOffsetByDefault(): void
    {
        // Arrange
        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        $output = new PdfStreamOutput($stream);

        // Act
        $pos = $output->position();

        // Assert
        self::assertSame(0, $pos);
        fclose($stream);
    }

    #[Test]
    public function positionReturnsCurrentOffset(): void
    {
        // Arrange
        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        $output = new PdfStreamOutput($stream);
        $output->write('Hello');

        // Act
        $pos = $output->position();

        // Assert
        self::assertSame(5, $pos);
        fclose($stream);
    }

    #[Test]
    public function positionReturnsZeroWhenStreamTellFails(): void
    {
        // Arrange
        $process = proc_open('cat', [0 => ['pipe', 'r'], 1 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        $stream = $pipes[0];
        $output = new PdfStreamOutput($stream);

        // Act
        $pos = $output->position();

        // Assert
        self::assertSame(0, $pos);
        fclose($pipes[0]);
        fclose($pipes[1]);
        proc_close($process);
    }
}
