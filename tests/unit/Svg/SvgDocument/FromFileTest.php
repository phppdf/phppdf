<?php

declare(strict_types=1);

namespace PhpPdf\Svg\SvgDocument;

use PhpPdf\Svg\SvgDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(SvgDocument::class)]
#[CoversMethod(SvgDocument::class, 'fromFile')]
final class FromFileTest extends TestCase
{
    #[Test]
    public function fromFileReturnsSvgDocumentForReadableFile(): void
    {
        // Arrange
        $path = tempnam(sys_get_temp_dir(), 'svg_') . '.svg';
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="50"/>');

        try {
            // Act
            $doc = SvgDocument::fromFile($path);

            // Assert
            self::assertInstanceOf(SvgDocument::class, $doc);
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function fromFileThrowsForUnreadablePath(): void
    {
        // Arrange
        $path = '/nonexistent/path/logo.svg';

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SVG file not readable:');

        // Act
        SvgDocument::fromFile($path);
    }
}
