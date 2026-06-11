<?php

declare(strict_types=1);

namespace PhpPdf\Text\RichTextBox;

use PhpPdf\Text\RichTextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RichTextBox::class)]
#[CoversMethod(RichTextBox::class, 'getLineHeight')]
final class GetLineHeightTest extends TestCase
{
    #[Test]
    public function getLineHeightReturnsExplicitValue(): void
    {
        // Arrange / Act
        $box = RichTextBox::create([], maxWidth: 200, lineHeight: 16.0);

        // Assert
        self::assertEqualsWithDelta(16.0, $box->getLineHeight(), 0.001);
    }

    #[Test]
    public function getLineHeightDefaultsTwelveForEmptySpans(): void
    {
        // Arrange / Act
        $box = RichTextBox::create([], maxWidth: 200);

        // Assert
        self::assertEqualsWithDelta(12.0, $box->getLineHeight(), 0.001);
    }
}
