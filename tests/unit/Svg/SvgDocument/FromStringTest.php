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
#[CoversMethod(SvgDocument::class, 'fromString')]
final class FromStringTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    #[Test]
    public function fromStringParsesViewBoxDimensions(): void
    {
        // Arrange — viewBox takes priority over width/height
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100"/>';

        // Act
        $doc = SvgDocument::fromString($svg);

        // Assert
        self::assertEqualsWithDelta(200.0, $doc->getWidth(), 0.001);
        self::assertEqualsWithDelta(100.0, $doc->getHeight(), 0.001);
    }

    #[Test]
    public function fromStringParsesWidthAndHeightAttributes(): void
    {
        // Arrange — no viewBox, use width + height
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="150"/>';

        // Act
        $doc = SvgDocument::fromString($svg);

        // Assert
        self::assertEqualsWithDelta(300.0, $doc->getWidth(), 0.001);
        self::assertEqualsWithDelta(150.0, $doc->getHeight(), 0.001);
    }

    #[Test]
    public function fromStringParsesWidthAndHeightWithUnits(): void
    {
        // Arrange — "200px" → strips "px" → 200.0
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200px" height="100pt"/>';

        // Act
        $doc = SvgDocument::fromString($svg);

        // Assert
        self::assertEqualsWithDelta(200.0, $doc->getWidth(), 0.001);
        self::assertEqualsWithDelta(100.0, $doc->getHeight(), 0.001);
    }

    #[Test]
    public function fromStringFallsBackToDefaultDimensionsWhenNonePresent(): void
    {
        // Arrange — no viewBox, no width, no height → fallback [100, 100]
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"/>';

        // Act
        $doc = SvgDocument::fromString($svg);

        // Assert
        self::assertEqualsWithDelta(100.0, $doc->getWidth(), 0.001);
        self::assertEqualsWithDelta(100.0, $doc->getHeight(), 0.001);
    }

    #[Test]
    public function fromStringFallsBackToDefaultDimensionsWhenViewBoxHasWrongPartCount(): void
    {
        // Arrange — viewBox with only 2 parts (invalid) → falls back to width/height
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0" width="80" height="40"/>';

        // Act
        $doc = SvgDocument::fromString($svg);

        // Assert — falls back to w/h because viewBox is invalid
        self::assertEqualsWithDelta(80.0, $doc->getWidth(), 0.001);
    }

    #[Test]
    public function fromStringFallsBackToDefaultDimensionsWhenViewBoxHasZeroWidth(): void
    {
        // Arrange — viewBox w=0 is invalid → falls back to width/height
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 0 100" width="80" height="40"/>';

        // Act
        $doc = SvgDocument::fromString($svg);

        // Assert — viewBox w=0 is invalid → falls back to w/h=80
        self::assertEqualsWithDelta(80.0, $doc->getWidth(), 0.001);
    }

    // -------------------------------------------------------------------------
    // Error paths
    // -------------------------------------------------------------------------

    #[Test]
    public function fromStringThrowsForInvalidXml(): void
    {
        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse SVG XML');

        // Act
        SvgDocument::fromString('not valid xml <<>>');
    }

    #[Test]
    public function fromStringThrowsWhenRootIsNotSvg(): void
    {
        // Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Root element is not <svg>');

        // Act
        SvgDocument::fromString('<html><body/></html>');
    }
}
