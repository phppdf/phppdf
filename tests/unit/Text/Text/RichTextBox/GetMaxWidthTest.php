<?php

declare(strict_types=1);

namespace PhpPdf\Text\RichTextBox;

use PhpPdf\Text\RichTextBox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RichTextBox::class)]
#[CoversMethod(RichTextBox::class, 'getMaxWidth')]
final class GetMaxWidthTest extends TestCase
{
    #[Test]
    public function getMaxWidthReturnsValuePassedToCreate(): void
    {
        // Arrange / Act
        $box = RichTextBox::create([], maxWidth: 350.5);

        // Assert
        self::assertEqualsWithDelta(350.5, $box->getMaxWidth(), 0.001);
    }
}
