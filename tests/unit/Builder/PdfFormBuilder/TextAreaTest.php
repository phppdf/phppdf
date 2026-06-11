<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfFormBuilder;

use PhpPdf\Builder\PdfFormBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFormBuilder::class)]
#[CoversMethod(PdfFormBuilder::class, 'textArea')]
final class TextAreaTest extends TestCase
{
    #[Test]
    public function textAreaReturnsSelf(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $result = $form->textArea('name', 10, 20, 100, 60);

        // Assert
        self::assertSame($form, $result);
    }

    #[Test]
    public function textAreaStoresFieldWithCorrectType(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $form->textArea('myArea', 10, 20, 100, 60);

        // Assert
        $fields = $form->getFields();
        self::assertCount(1, $fields);
        self::assertSame('text', $fields[0]['type']);
        self::assertSame('myArea', $fields[0]['name']);
    }

    #[Test]
    public function textAreaIsMultiLine(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $form->textArea('area', 0, 0, 50, 60);

        // Assert
        self::assertTrue($form->getFields()[0]['multi']);
    }
}
