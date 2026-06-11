<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Color\Color;
use PhpPdf\Compliance\PdfAConformance;
use PhpPdf\Compliance\PdfAIssueLevel;
use PhpPdf\Compliance\PdfAValidationResult;
use PhpPdf\Compliance\PdfAValidator;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Font\TrueTypeFont;
use PhpPdf\Object\PdfGraphicsStateDictionary;
use PhpPdf\Output\PdfFileOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;

function generate(): void
{
    $pH = PdfPageSize::A4[1];

    // -------------------------------------------------------------------------
    // Helper: print a validation result
    // -------------------------------------------------------------------------

    $printResult = static function (PdfAValidationResult $result, string $label): void {
        $status = $result->isCompliant()
            ? 'PASS'
            : 'FAIL';
        $claimed = 'PDF/A-' . $result->conformance->value;

        echo PHP_EOL;
        echo str_repeat('─', 60) . PHP_EOL;
        printf("[%s]  %s  (%s)\n", $status, $label, $claimed);
        echo str_repeat('─', 60) . PHP_EOL;

        if ($result->issues === []) {
            echo '  No issues found.' . PHP_EOL;

            return;
        }

        foreach ($result->issues as $issue) {
            $tag = $issue->level === PdfAIssueLevel::Error
                ? 'ERROR  '
                : 'WARNING';
            // Wrap long messages at 80 chars.
            $lines = explode("\n", wordwrap($issue->message, 72, "\n", false));
            echo "  {$tag}  [{$issue->rule}]" . PHP_EOL;

            foreach ($lines as $line) {
                echo "           {$line}" . PHP_EOL;
            }
        }

        printf(
            PHP_EOL . "  %d error(s), %d warning(s)\n",
            $result->getErrorCount(),
            $result->getWarningCount(),
        );
    };

    // -------------------------------------------------------------------------
    // Test A: PDF/A-2b with embedded TrueType font — expected to be nearly clean.
    // -------------------------------------------------------------------------

    $fontPath = '/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf';
    $font = TrueTypeFont::fromFile($fontPath);

    $pathA = '/tmp/phppdf-pdfa-valid.pdf';

    $docA = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('PDF/A-2b Compliant Document')
                ->author('phppdf')
                ->subject('Embedded-font PDF/A-2b test'),
        )
        ->conformTo(PdfAConformance::PdfA2b)
        ->page(static function (PdfPageBuilder $page) use ($pH, $font): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useEmbeddedFont('F1', $font)
                ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                    $s->beginText()->setFont('F1', 20)
                      ->setTextMatrix(Matrix::translate(72, $pH - 80))
                      ->showText('PDF/A-2b test document — embedded font')
                      ->endText();

                    $s->beginText()->setFont('F1', 12)
                      ->setTextMatrix(Matrix::translate(72, $pH - 120))
                      ->showText('This page uses an embedded TrueType font (NotoSans).')
                      ->endText();
                });
        })
        ->build();

    (new PdfDocumentSerializer(new PdfFileOutput($pathA)))->writeDocument($docA);
    $printResult(PdfAValidator::validateFile($pathA, PdfAConformance::PdfA2b), 'Embedded font + conformTo(PdfA2b)');

    // -------------------------------------------------------------------------
    // Test B: PDF/A-2b claimed but using standard Type 1 fonts (not embedded).
    // This is the most common mistake — phppdf's standard Type1 usage.
    // -------------------------------------------------------------------------

    $pathB = '/tmp/phppdf-pdfa-type1.pdf';

    $docB = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('PDF/A-2b with Type1 fonts')
                ->author('phppdf'),
        )
        ->conformTo(PdfAConformance::PdfA2b)
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->useType1Font('F2', 'Helvetica-Bold')
                ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                    $s->beginText()->setFont('F2', 16)
                      ->setTextMatrix(Matrix::translate(72, $pH - 80))
                      ->showText('Standard Type 1 fonts are not PDF/A compliant.')
                      ->endText();
                    $s->beginText()->setFont('F1', 12)
                      ->setTextMatrix(Matrix::translate(72, $pH - 120))
                      ->showText('Helvetica and Helvetica-Bold are not embedded in this file.')
                      ->endText();
                });
        })
        ->build();

    (new PdfDocumentSerializer(new PdfFileOutput($pathB)))->writeDocument($docB);
    $printResult(PdfAValidator::validateFile(
        $pathB,
        PdfAConformance::PdfA2b,
    ), 'Standard Type1 font + conformTo(PdfA2b)');

    // -------------------------------------------------------------------------
    // Test C: PDF/A-1b claimed with transparency (opacity < 1.0).
    // Transparency is forbidden in PDF/A-1b but allowed in PDF/A-2b.
    // -------------------------------------------------------------------------

    $pathC = '/tmp/phppdf-pdfa-transparency.pdf';

    $docC = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('PDF/A-1b transparency test')
                ->author('phppdf'),
        )
        ->conformTo(PdfAConformance::PdfA1b)
        ->page(static function (PdfPageBuilder $page) use ($pH, $font): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useEmbeddedFont('F1', $font)
                ->useGraphicsState('GSHalf', new PdfGraphicsStateDictionary(fillAlpha: 0.5))
                ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                    $s->beginText()->setFont('F1', 16)
                      ->setTextMatrix(Matrix::translate(72, $pH - 80))
                      ->showText('This page uses transparency (fill alpha 0.5).')
                      ->endText();

                    $s->setGraphicsStateParameters('GSHalf')
                      ->fillColor(Color::rgb(0.2, 0.4, 0.8))
                      ->rectangle(72, 300, 200, 100)->fill();
                });
        })
        ->build();

    (new PdfDocumentSerializer(new PdfFileOutput($pathC)))->writeDocument($docC);
    $printResult(PdfAValidator::validateFile($pathC, PdfAConformance::PdfA1b), 'Transparency + conformTo(PdfA1b)');

    // -------------------------------------------------------------------------
    // Test D: A plain PDF with no conformTo() — validate against PDF/A-2b.
    // All structural checks should fail.
    // -------------------------------------------------------------------------

    $pathD = '/tmp/phppdf-no-pdfa.pdf';

    $docD = (new PdfDocumentBuilder())
        ->info((new PdfDocumentInfo())->title('Plain PDF (no PDF/A)'))
        ->page(static function (PdfPageBuilder $page) use ($pH): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $s) use ($pH): void {
                    $s->beginText()->setFont('F1', 14)
                      ->setTextMatrix(Matrix::translate(72, $pH - 80))
                      ->showText('Ordinary PDF — no PDF/A metadata.')
                      ->endText();
                });
        })
        ->build();

    (new PdfDocumentSerializer(new PdfFileOutput($pathD)))->writeDocument($docD);
    $printResult(PdfAValidator::validateFile($pathD, PdfAConformance::PdfA2b), 'Plain PDF validated against PDF/A-2b');

    // -------------------------------------------------------------------------
    // Summary
    // -------------------------------------------------------------------------

    echo PHP_EOL;
    echo str_repeat('─', 60) . PHP_EOL;
    echo 'Files written to /tmp/:' . PHP_EOL;

    foreach ([$pathA, $pathB, $pathC, $pathD] as $p) {
        echo "  {$p}" . PHP_EOL;
    }
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
