<?php

declare(strict_types=1);

namespace PhpPdf\Output\PdfFileOutput;

use PhpPdf\Output\PdfFileOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PdfFileOutput::class)]
#[CoversMethod(PdfFileOutput::class, '__construct')]
final class ConstructorTest extends TestCase
{
    #[Test]
    public function constructorThrowsRuntimeExceptionForInvalidPath(): void
    {
        // Arrange — suppress the fopen warning that PHP emits for a bad path
        set_error_handler(static fn (): bool => true);

        try {
            // Act / Assert
            $this->expectException(RuntimeException::class);
            new PdfFileOutput('/nonexistent/directory/file.pdf');
        } finally {
            restore_error_handler();
        }
    }
}
