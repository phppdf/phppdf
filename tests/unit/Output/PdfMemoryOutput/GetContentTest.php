<?php

declare(strict_types=1);

namespace PhpPdf\Output\PdfMemoryOutput;

use PhpPdf\Output\PdfMemoryOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfMemoryOutput::class)]
#[CoversMethod(PdfMemoryOutput::class, 'getContent')]
final class GetContentTest extends TestCase
{
    #[Test]
    public function getContentIsEmptyInitially(): void
    {
        self::assertSame('', (new PdfMemoryOutput())->getContent());
    }

    #[Test]
    public function getContentReturnsAllWrittenData(): void
    {
        // Arrange
        $output = new PdfMemoryOutput();
        $output->write('ABC');

        // Act / Assert
        self::assertSame('ABC', $output->getContent());
    }
}
