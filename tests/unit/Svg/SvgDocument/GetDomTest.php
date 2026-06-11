<?php

declare(strict_types=1);

namespace PhpPdf\Svg\SvgDocument;

use DOMDocument;
use DOMElement;
use PhpPdf\Svg\SvgDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SvgDocument::class)]
#[CoversMethod(SvgDocument::class, 'getDom')]
final class GetDomTest extends TestCase
{
    #[Test]
    public function getDomReturnsDomDocument(): void
    {
        // Arrange
        $doc = SvgDocument::fromString('<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>');

        // Act
        $dom = $doc->getDom();

        // Assert
        self::assertInstanceOf(DOMDocument::class, $dom);
        self::assertInstanceOf(DOMElement::class, $dom->documentElement);
        self::assertSame('svg', $dom->documentElement->localName);
    }
}
