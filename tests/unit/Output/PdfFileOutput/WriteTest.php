<?php

declare(strict_types=1);

namespace PhpPdf\Output\PdfFileOutput;

use PhpPdf\Output\PdfFileOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFileOutput::class)]
#[CoversMethod(PdfFileOutput::class, 'write')]
final class WriteTest extends TestCase
{
    #[Test]
    public function writeAppendsContentToFile(): void
    {
        // Arrange
        $path = tempnam(sys_get_temp_dir(), 'phppdf_test_');
        $output = new PdfFileOutput($path);

        // Act
        $output->write('Hello');
        $output->write(' World');

        // Assert
        self::assertSame('Hello World', file_get_contents($path));

        // Cleanup
        unlink($path);
    }
}
