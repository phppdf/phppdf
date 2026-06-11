<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfFormBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfFileOutput;
use PhpPdf\Reader\PdfAcroFormFiller;
use PhpPdf\Reader\PdfAcroFormReader;
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $blankPath  = '/tmp/phppdf-form-blank.pdf';
    $filledPath = '/tmp/phppdf-form-filled.pdf';

    // -------------------------------------------------------------------------
    // Step 1: Build a simple form PDF with several field types.
    // -------------------------------------------------------------------------

    [$pageW, $pageH] = PdfPageSize::A4;
    $lm     = 72.0;
    $fieldX = $lm + 130.0;
    $fieldW = $pageW - $fieldX - $lm;
    $fh     = 20.0;
    $cbSize = 14.0;

    $fnBot  = $pageH - 160.0;
    $lnBot  = $fnBot  - ($fh + 8.0);
    $emBot  = $lnBot  - ($fh + 8.0);
    $coBot  = $emBot  - ($fh + 8.0);
    $msgBot = $coBot  - (60.0 + 28.0);
    $nlBot  = $msgBot - (14.0 + 8.0 + 10.0);

    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Form Reading Demo')
                ->author('phppdf'),
        )
        ->page(function (PdfPageBuilder $page) use (
            $lm,
            $fieldX,
            $fieldW,
            $fh,
            $cbSize,
            $fnBot,
            $lnBot,
            $emBot,
            $coBot,
            $msgBot,
            $nlBot,
        ): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('FB', 'Helvetica-Bold')
                ->content(function (PdfContentStreamBuilder $s) use (
                    $lm,
                    $fieldX,
                    $fieldW,
                    $fh,
                    $cbSize,
                    $fnBot,
                    $lnBot,
                    $emBot,
                    $coBot,
                    $msgBot,
                    $nlBot,
                ): void {
                    $s->beginText()->setFont('FB', 16)
                      ->setTextMatrix(Matrix::translate($lm, 780))
                      ->showText('Registration Form')->endText();

                    $rows = [
                        ['First name',  $fnBot],
                        ['Last name',   $lnBot],
                        ['Email',       $emBot],
                        ['Country',     $coBot],
                        ['Message',     $msgBot],
                    ];
                    foreach ($rows as [$label, $bot]) {
                        $s->beginText()->setFont('F1', 10)
                          ->setTextMatrix(Matrix::translate($lm, $bot + 6))
                          ->showText($label)->endText();
                    }

                    $s->beginText()->setFont('F1', 10)
                      ->setTextMatrix(Matrix::translate($fieldX + $cbSize + 6, $nlBot + 3))
                      ->showText('Subscribe to newsletter')->endText();
                });
        })
        ->form(function (PdfFormBuilder $form) use (
            $fieldX,
            $fieldW,
            $fh,
            $cbSize,
            $fnBot,
            $lnBot,
            $emBot,
            $coBot,
            $msgBot,
            $nlBot,
        ): void {
            $form
                ->textField('firstName', $fieldX, $fnBot, $fieldW, $fh)
                ->textField('lastName', $fieldX, $lnBot, $fieldW, $fh)
                ->textField('email', $fieldX, $emBot, $fieldW, $fh)
                ->comboBox(
                    'country',
                    $fieldX,
                    $coBot,
                    $fieldW,
                    $fh,
                    options: ['', 'Australia', 'Canada', 'France', 'Germany',
                    'Netherlands',
                    'United Kingdom',
                    'United States'],
                )
                ->textArea('message', $fieldX, $msgBot, $fieldW, 60.0)
                ->checkbox('newsletter', $fieldX, $nlBot, $cbSize);
        })
        ->build();

    $output = new PdfFileOutput($blankPath);
    (new PdfDocumentSerializer($output))->writeDocument($document);
    echo "Written (blank) : {$blankPath}" . PHP_EOL;

    // -------------------------------------------------------------------------
    // Step 2: Read the blank form — list all fields and their current values.
    // -------------------------------------------------------------------------

    $doc    = PdfDocumentReader::open($blankPath);
    $reader = new PdfAcroFormReader($doc);
    $fields = $reader->getFields();

    echo PHP_EOL . "Fields in blank form:" . PHP_EOL;
    foreach ($fields as $field) {
        $typeName = $field->type->name;
        $value    = match (true) {
            $field->value === null  => '(empty)',
            $field->value === true  => 'checked',
            $field->value === false => 'unchecked',
            default                 => var_export($field->value, true),
        };
        $opts = $field->options ? ' [opts: ' . implode(', ', $field->options) . ']' : '';
        echo "  {$field->fullName} ({$typeName}): {$value}{$opts}" . PHP_EOL;
    }

    // -------------------------------------------------------------------------
    // Step 3: Fill the form using an incremental update.
    // -------------------------------------------------------------------------

    $originalBytes = file_get_contents($blankPath);
    $filler = new PdfAcroFormFiller($doc, $originalBytes);

    $fieldMap = $reader->getFieldsByName();

    if (isset($fieldMap['firstName'])) {
        $filler->setText($fieldMap['firstName'], 'Jane');
    }
    if (isset($fieldMap['lastName'])) {
        $filler->setText($fieldMap['lastName'], 'Doe');
    }
    if (isset($fieldMap['email'])) {
        $filler->setText($fieldMap['email'], 'jane.doe@example.com');
    }
    if (isset($fieldMap['country'])) {
        $filler->setChoice($fieldMap['country'], 'Netherlands');
    }
    if (isset($fieldMap['message'])) {
        $filler->setText($fieldMap['message'], "Hello,\nThis is a test message written via phppdf.");
    }
    if (isset($fieldMap['newsletter'])) {
        $filler->setChecked($fieldMap['newsletter'], true);
    }

    $filler->save($filledPath);
    echo PHP_EOL . "Written (filled): {$filledPath}" . PHP_EOL;

    // -------------------------------------------------------------------------
    // Step 4: Re-read the filled PDF and verify the values were stored.
    // -------------------------------------------------------------------------

    $filledDoc    = PdfDocumentReader::open($filledPath);
    $filledReader = new PdfAcroFormReader($filledDoc);
    $filledFields = $filledReader->getFields();

    echo PHP_EOL . "Fields after filling:" . PHP_EOL;
    foreach ($filledFields as $field) {
        $typeName = $field->type->name;
        $value    = match (true) {
            $field->value === null  => '(empty)',
            $field->value === true  => 'checked',
            $field->value === false => 'unchecked',
            default                 => var_export($field->value, true),
        };
        echo "  {$field->fullName} ({$typeName}): {$value}" . PHP_EOL;
    }
}

(function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
