<?php

declare(strict_types=1);

namespace PhpPdf\Output\PdfFileOutput;

use PhpPdf\Output\PdfFileOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(PdfFileOutput::class)]
#[CoversMethod(PdfFileOutput::class, 'position')]
final class PositionTest extends TestCase
{
    #[Test]
    public function positionReturnsCurrentByteOffset(): void
    {
        // Arrange
        $path = tempnam(sys_get_temp_dir(), 'phppdf_test_');
        $output = new PdfFileOutput($path);

        // Act
        $output->write('Hello');

        // Assert
        self::assertSame(5, $output->position());

        // Cleanup
        unlink($path);
    }

    #[Test]
    public function positionIsZeroInitially(): void
    {
        // Arrange
        $path = tempnam(sys_get_temp_dir(), 'phppdf_test_');
        $output = new PdfFileOutput($path);

        // Assert
        self::assertSame(0, $output->position());

        // Cleanup
        unlink($path);
    }

    #[Test]
    public function positionReturnsZeroWhenFtellFails(): void
    {
        // Arrange — replace the underlying handle with a proc_open pipe,
        // for which ftell() returns false
        $path = tempnam(sys_get_temp_dir(), 'phppdf_test_');
        $output = new PdfFileOutput($path);

        $process = proc_open('cat', [0 => ['pipe', 'r'], 1 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);

        $reflection = new ReflectionProperty(PdfFileOutput::class, 'handle');
        $original = $reflection->getValue($output);
        $reflection->setValue($output, $pipes[0]);

        // Act
        $position = $output->position();

        // Assert
        self::assertSame(0, $position);

        // Cleanup
        $reflection->setValue($output, $original);
        fclose($pipes[0]);
        fclose($pipes[1]);
        proc_close($process);
        unlink($path);
    }
}
