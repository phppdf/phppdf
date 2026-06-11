<?php

declare(strict_types=1);

namespace PhpPdf\Output\PdfFileOutput;

use PhpPdf\Output\PdfFileOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFileOutput::class)]
#[CoversMethod(PdfFileOutput::class, '__destruct')]
final class DestructTest extends TestCase
{
    #[Test]
    public function destructorClosesFileHandle(): void
    {
        // Arrange
        $path = tempnam(sys_get_temp_dir(), 'phppdf_test_');
        $output = new PdfFileOutput($path);
        $output->write('data');

        // Act — force destructor by unsetting
        unset($output);

        // Assert — file should be properly closed and readable
        self::assertSame('data', file_get_contents($path));

        // Cleanup
        unlink($path);
    }
}
