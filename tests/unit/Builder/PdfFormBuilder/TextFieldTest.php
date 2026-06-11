<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfFormBuilder;

use PhpPdf\Builder\PdfFormBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFormBuilder::class)]
#[CoversMethod(PdfFormBuilder::class, 'textField')]
final class TextFieldTest extends TestCase
{
    #[Test]
    public function textFieldReturnsSelf(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $result = $form->textField('name', 10, 20, 100, 20);

        // Assert
        self::assertSame($form, $result);
    }

    #[Test]
    public function textFieldStoresFieldWithCorrectType(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $form->textField('myField', 10, 20, 100, 20);

        // Assert
        $fields = $form->getFields();
        self::assertCount(1, $fields);
        self::assertSame('text', $fields[0]['type']);
        self::assertSame('myField', $fields[0]['name']);
    }

    #[Test]
    public function textFieldIsNotMultiLine(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $form->textField('field', 0, 0, 50, 20);

        // Assert
        self::assertFalse($form->getFields()[0]['multi']);
    }
}
