<?php

declare(strict_types=1);

namespace PhpPdf\Builder;

/**
 * Accumulates interactive form field definitions for a single document.
 *
 * Obtain an instance from PdfDocumentBuilder::form() and add fields via the
 * fluent methods below. All coordinates use PDF page-space (origin at the
 * bottom-left of the page, y increases upward).
 *
 * Supported field types:
 *   textField() — single-line text input
 *   textArea() — multi-line text input
 *   checkbox() — on/off toggle with drawn appearance
 *   comboBox() — drop-down list
 *
 * @phpstan-type FieldSpec array{
 *     type: string,
 *     name: string,
 *     x: float,
 *     y: float,
 *     width: float,
 *     height: float,
 *     page: int,
 *     value: string,
 *     tooltip: string,
 *     fontSize: float,
 *     readOnly: bool,
 *     multi: bool,
 *     checked: bool,
 *     options: array<string>,
 * }
 */
final class PdfFormBuilder
{
    /** @var list<FieldSpec> */
    private array $fields = [];

    /**
     * Adds a single-line text input.
     *
     * @param string $name     Unique field name (used in exported form data).
     * @param float $x        Left edge in page points.
     * @param float $y        Bottom edge in page points.
     * @param float $width    Field width in points.
     * @param float $height   Field height in points.
     * @param int $page     0-based page index.
     * @param string $value    Pre-filled value shown when the document is opened.
     * @param string $tooltip  Tooltip shown on hover (stored as /TU).
     * @param float $fontSize Font size for entered text.
     * @param bool $readOnly When true the field cannot be edited.
     */
    public function textField(
        string $name,
        float $x,
        float $y,
        float $width,
        float $height,
        int $page = 0,
        string $value = '',
        string $tooltip = '',
        float $fontSize = 10.0,
        bool $readOnly = false,
    ): self {
        $this->fields[] = [
            'checked' => false,
            'fontSize' => $fontSize,
            'height' => $height,
            'multi' => false,
            'name' => $name,
            'options' => [],
            'page' => $page,
            'readOnly' => $readOnly,
            'tooltip' => $tooltip,
            'type' => 'text',
            'value' => $value,
            'width' => $width,
            'x' => $x,
            'y' => $y,
        ];

        return $this;
    }

    /**
     * Adds a multi-line text area.
     *
     * Same parameters as textField(). The viewer wraps long lines and shows a
     * scrollbar when the text exceeds the visible area.
     */
    public function textArea(
        string $name,
        float $x,
        float $y,
        float $width,
        float $height,
        int $page = 0,
        string $value = '',
        string $tooltip = '',
        float $fontSize = 10.0,
        bool $readOnly = false,
    ): self {
        $this->fields[] = [
            'checked' => false,
            'fontSize' => $fontSize,
            'height' => $height,
            'multi' => true,
            'name' => $name,
            'options' => [],
            'page' => $page,
            'readOnly' => $readOnly,
            'tooltip' => $tooltip,
            'type' => 'text',
            'value' => $value,
            'width' => $width,
            'x' => $x,
            'y' => $y,
        ];

        return $this;
    }

    /**
     * Adds a checkbox.
     *
     * @param string $name    Unique field name.
     * @param float $x       Left edge in page points.
     * @param float $y       Bottom edge in page points.
     * @param float $size    Width and height of the checkbox in points.
     * @param int $page    0-based page index.
     * @param bool $checked Whether the checkbox is ticked by default.
     * @param string $tooltip Tooltip shown on hover.
     */
    public function checkbox(
        string $name,
        float $x,
        float $y,
        float $size = 12.0,
        int $page = 0,
        bool $checked = false,
        string $tooltip = '',
    ): self {
        $this->fields[] = [
            'checked' => $checked,
            'fontSize' => 10.0,
            'height' => $size,
            'multi' => false,
            'name' => $name,
            'options' => [],
            'page' => $page,
            'readOnly' => false,
            'tooltip' => $tooltip,
            'type' => 'checkbox',
            'value' => '',
            'width' => $size,
            'x' => $x,
            'y' => $y,
        ];

        return $this;
    }

    /**
     * Adds a combo box (drop-down list).
     *
     * @param string $name     Unique field name.
     * @param float $x        Left edge in page points.
     * @param float $y        Bottom edge in page points.
     * @param float $width    Field width in points.
     * @param float $height   Field height in points.
     * @param array<string> $options  List of selectable option strings.
     * @param int $page     0-based page index.
     * @param string $value    Pre-selected option (must be one of $options or empty).
     * @param string $tooltip  Tooltip shown on hover.
     * @param float $fontSize Font size for the displayed value.
     * @param bool $readOnly When true the field cannot be changed.
     */
    public function comboBox(
        string $name,
        float $x,
        float $y,
        float $width,
        float $height,
        array $options,
        int $page = 0,
        string $value = '',
        string $tooltip = '',
        float $fontSize = 10.0,
        bool $readOnly = false,
    ): self {
        $this->fields[] = [
            'checked' => false,
            'fontSize' => $fontSize,
            'height' => $height,
            'multi' => false,
            'name' => $name,
            'options' => $options,
            'page' => $page,
            'readOnly' => $readOnly,
            'tooltip' => $tooltip,
            'type' => 'combo',
            'value' => $value,
            'width' => $width,
            'x' => $x,
            'y' => $y,
        ];

        return $this;
    }

    /** @return list<FieldSpec> */
    public function getFields(): array
    {
        return $this->fields;
    }
}
