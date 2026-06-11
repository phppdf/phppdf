<?php

declare(strict_types=1);

namespace PhpPdf\Font\TrueTypeFont;

use PhpPdf\Font\TrueTypeFont;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(TrueTypeFont::class)]
#[CoversMethod(TrueTypeFont::class, 'fromFile')]
final class FromFileTest extends TestCase
{
    #[Test]
    public function fromFileReturnsFontForReadableFile(): void
    {
        // Arrange
        $path = '/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf';

        // Act
        $font = TrueTypeFont::fromFile($path);

        // Assert
        self::assertInstanceOf(TrueTypeFont::class, $font);
        self::assertNotEmpty($font->getFontName());
    }

    #[Test]
    public function fromFileThrowsForUnreadablePath(): void
    {
        // Arrange
        $path = '/nonexistent/font/path/does_not_exist.ttf';

        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Font file not readable:');

        // Act
        TrueTypeFont::fromFile($path);
    }
}
