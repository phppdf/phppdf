<?php

declare(strict_types=1);

namespace PhpPdf\Svg\SvgDocument;

use PhpPdf\Svg\SvgDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SvgDocument::class)]
#[CoversMethod(SvgDocument::class, 'getWidth')]
final class GetWidthTest extends TestCase
{
    #[Test]
    public function getWidthReturnsDocumentWidth(): void
    {
        // Arrange
        $doc = SvgDocument::fromString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 250 80"/>');

        // Act / Assert
        self::assertEqualsWithDelta(250.0, $doc->getWidth(), 0.001);
    }
}
