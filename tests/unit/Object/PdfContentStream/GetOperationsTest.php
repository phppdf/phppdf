<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfContentStream;

use PhpPdf\Content\Operation\BeginText;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStream::class)]
#[CoversMethod(PdfContentStream::class, 'getOperations')]
#[UsesClass(BeginText::class)]
final class GetOperationsTest extends TestCase
{
    #[Test]
    public function getOperationsReturnsStoredOperations(): void
    {
        // Arrange
        $ops = [new BeginText()];
        $stream = new PdfContentStream($ops);

        // Act / Assert
        self::assertSame($ops, $stream->getOperations());
    }
}
