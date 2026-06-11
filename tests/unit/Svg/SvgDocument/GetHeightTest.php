<?php

declare(strict_types=1);

namespace PhpPdf\Svg\SvgDocument;

use PhpPdf\Svg\SvgDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SvgDocument::class)]
#[CoversMethod(SvgDocument::class, 'getHeight')]
final class GetHeightTest extends TestCase
{
    #[Test]
    public function getHeightReturnsDocumentHeight(): void
    {
        // Arrange
        $doc = SvgDocument::fromString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 250 80"/>');

        // Act / Assert
        self::assertEqualsWithDelta(80.0, $doc->getHeight(), 0.001);
    }
}
