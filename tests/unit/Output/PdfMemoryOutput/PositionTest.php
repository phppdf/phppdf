<?php

declare(strict_types=1);

namespace PhpPdf\Output\PdfMemoryOutput;

use PhpPdf\Output\PdfMemoryOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfMemoryOutput::class)]
#[CoversMethod(PdfMemoryOutput::class, 'position')]
final class PositionTest extends TestCase
{
    #[Test]
    public function positionIsZeroWhenEmpty(): void
    {
        self::assertSame(0, (new PdfMemoryOutput())->position());
    }

    #[Test]
    public function positionTracksWrittenBytes(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $output->write('hello');

        // Act / Assert
        self::assertSame(5, $output->position());
    }
}
