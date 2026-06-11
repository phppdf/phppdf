<?php

declare(strict_types=1);

namespace PhpPdf\Compliance;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfReadPage;
use PhpPdf\Serialization\PdfStreamSerializer;
use Throwable;

/**
 * Validates that a PDF file meets PDF/A-1b or PDF/A-2b conformance requirements.
 *
 * Checks performed:
 *   - Trailer /ID present (§6.7.2)
 *   - No encryption (§6.1.2)
 *   - PDF version appropriate for the claimed level (§6.1.2)
 *   - Catalog /Metadata present and unfiltered (§6.7.2)
 *   - XMP pdfaid:part and pdfaid:conformance match the claimed level (§6.7.2)
 *   - /OutputIntents in catalog (§6.2.3, warning)
 *   - No forbidden action types (§6.6.1)
 *   - AcroForm /DR fonts embedded (§6.3.3)
 *   - All page /MediaBox present (§6.7.3, warning)
 *   - All page fonts embedded with font programs (§6.3.3)
 *   - Composite fonts have /ToUnicode CMaps (warning)
 *   - PDF/A-1b: No soft masks, sub-unity opacity, or transparency groups (§6.2.2)
 *
 * Fonts shared across pages are checked only once (deduplication by object identity).
 */
final class PdfAValidator
{
    private const array STANDARD_TYPE1_FONTS = [
        'Times-Roman', 'Helvetica', 'Courier', 'Symbol',
        'Times-Bold', 'Helvetica-Bold', 'Courier-Bold', 'ZapfDingbats',
        'Times-Italic', 'Helvetica-Oblique', 'Courier-Oblique',
        'Times-BoldItalic', 'Helvetica-BoldOblique', 'Courier-BoldOblique',
    ];

    private const array FORBIDDEN_ACTIONS = [
        'JavaScript', 'Launch', 'Sound', 'Movie', 'ResetForm', 'ImportData',
    ];

    /** @var list<\PhpPdf\Compliance\PdfAValidationIssue> */
    private array $issues;

    private PdfReadDocument $doc;
    private PdfAConformance $conformance;

    /** @var array<int, bool> Tracks already-checked font objects by spl_object_id. */
    private array $checkedFonts;

    // -------------------------------------------------------------------------
    // Public entry points
    // -------------------------------------------------------------------------

    public function validate(PdfReadDocument $document, PdfAConformance $conformance): PdfAValidationResult
    {
        $this->issues = [];
        $this->doc = $document;
        $this->conformance = $conformance;
        $this->checkedFonts = [];

        $this->checkTrailerId();
        $this->checkNoEncryption();
        $this->checkPdfVersion();

        $catalog = $this->readCatalog();

        if ($catalog !== null) {
            $this->checkMetadataPresent($catalog);
            $this->checkXmpMetadata($catalog);
            $this->checkOutputIntents($catalog);
            $this->checkForbiddenActions($catalog);
            $this->checkAcroFormFonts($catalog);
        }

        $this->checkPages();

        return new PdfAValidationResult($conformance, $this->issues);
    }

    /**
     * Convenience wrapper: opens the file and validates in one call.
     */
    public static function validateFile(string $path, PdfAConformance $conformance): PdfAValidationResult
    {
        return (new self())->validate(PdfDocumentReader::open($path), $conformance);
    }

    // -------------------------------------------------------------------------
    // Structural checks (trailer / catalog)
    // -------------------------------------------------------------------------

    private function checkTrailerId(): void
    {
        $id = $this->doc->getTrailer()->get('ID');

        if ($id instanceof PdfArray && count($id->getItems()) >= 2) {
            return;
        }

        $this->error(
            'trailer.id',
            'Trailer must contain a /ID array with two entries (ISO 19005 §6.7.2). '
            . 'Call PdfDocumentBuilder::conformTo() to add /ID automatically.',
        );
    }

    private function checkNoEncryption(): void
    {
        if ($this->doc->getTrailer()->get('Encrypt') === null) {
            return;
        }

        $this->error(
            'no-encryption',
            'Encrypted documents cannot conform to PDF/A (ISO 19005 §6.1.2). '
            . 'Remove the encrypt() call from PdfDocumentBuilder.',
        );
    }

    private function checkPdfVersion(): void
    {
        $version = $this->doc->getVersion();
        $part = $this->conformance->part();
        $minVersion = $part === 1
            ? PdfVersion::PDF_1_4
            : PdfVersion::PDF_1_7;

        if (!version_compare($version->value, $minVersion->value, '<')) {
            return;
        }

        $this->warn(
            'pdf-version',
            "PDF version {$version->value} is below the minimum {$minVersion->value} "
            . "required by PDF/A-{$part} (ISO 19005 §6.1.2).",
        );
    }

    private function readCatalog(): ?PdfDictionary
    {
        try {
            return $this->doc->getCatalog();
        } catch (Throwable $e) {
            $this->error('catalog', 'Document catalog could not be read: ' . $e->getMessage());

            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Metadata checks
    // -------------------------------------------------------------------------

    private function checkMetadataPresent(PdfDictionary $catalog): void
    {
        $metaRef = $catalog->get('Metadata');

        if ($metaRef === null) {
            $this->error(
                'metadata.present',
                '/Metadata entry missing from document catalog (ISO 19005 §6.7.2). '
                . 'Call PdfDocumentBuilder::conformTo() to add it automatically.',
            );

            return;
        }

        $meta = $this->doc->resolveObject($metaRef);

        if (!$meta instanceof PdfStream) {
            $this->error('metadata.type', '/Metadata entry must be a stream object (ISO 19005 §6.7.2).');

            return;
        }

        if ($meta->getDictionary()->get('Filter') === null) {
            return;
        }

        $this->error('metadata.unfiltered', '/Metadata stream must not be compressed or filtered (ISO 19005 §6.7.2).');
    }

    private function checkXmpMetadata(PdfDictionary $catalog): void
    {
        $metaRef = $catalog->get('Metadata');

        if ($metaRef === null) {
            return;
        }

        $meta = $this->doc->resolveObject($metaRef);

        if (!$meta instanceof PdfStream) {
            return;
        }

        // If the stream is filtered we already reported metadata.unfiltered — skip XMP parsing.
        if ($meta->getDictionary()->get('Filter') !== null) {
            return;
        }

        $xmp = $meta->getData()->serialize(new PdfStreamSerializer());

        $expectedPart = (string) $this->conformance->part();
        $expectedLevel = $this->conformance->conformanceLevel();

        if (preg_match('/<pdfaid:part\b[^>]*>(\d+)<\/pdfaid:part>/', $xmp, $m)) {
            if ($m[1] !== $expectedPart) {
                $this->error(
                    'xmp.pdfaid.part',
                    "XMP pdfaid:part is '{$m[1]}', expected '{$expectedPart}' "
                    . "for PDF/A-{$expectedPart} (ISO 19005 §6.7.2).",
                );
            }
        } else {
            $this->error('xmp.pdfaid.part', 'XMP metadata is missing the pdfaid:part element (ISO 19005 §6.7.2).');
        }

        if (preg_match('/<pdfaid:conformance\b[^>]*>([^<]+)<\/pdfaid:conformance>/', $xmp, $m)) {
            if (strtoupper(trim($m[1])) !== $expectedLevel) {
                $this->error(
                    'xmp.pdfaid.conformance',
                    "XMP pdfaid:conformance is '{$m[1]}', expected '{$expectedLevel}' "
                    . "(ISO 19005 §6.7.2).",
                );
            }
        } else {
            $this->error(
                'xmp.pdfaid.conformance',
                'XMP metadata is missing the pdfaid:conformance element (ISO 19005 §6.7.2).',
            );
        }

        if (!str_contains($xmp, 'application/pdf')) {
            $this->warn(
                'xmp.dc.format',
                'XMP metadata should declare dc:format = "application/pdf" (ISO 19005 §6.7.2).',
            );
        }

        if (!str_contains($xmp, 'xmlns:dc=')) {
            $this->warn('xmp.ns.dc', 'XMP metadata is missing the Dublin Core (dc:) namespace declaration.');
        }

        if (str_contains($xmp, 'xmlns:xmp=')) {
            return;
        }

        $this->warn('xmp.ns.xmp', 'XMP metadata is missing the XMP Basic (xmp:) namespace declaration.');
    }

    // -------------------------------------------------------------------------
    // Catalog-level checks
    // -------------------------------------------------------------------------

    private function checkOutputIntents(PdfDictionary $catalog): void
    {
        if ($catalog->get('OutputIntents') !== null) {
            return;
        }

        $this->warn(
            'output-intents',
            '/OutputIntents missing from catalog. Documents using device-dependent colorspaces '
            . '(DeviceRGB, DeviceCMYK, DeviceGray) require an output intent with an ICC profile '
            . '(ISO 19005 §6.2.3).',
        );
    }

    private function checkForbiddenActions(PdfDictionary $catalog): void
    {
        $openAction = $catalog->get('OpenAction');

        if ($openAction !== null) {
            $action = $this->doc->resolveObject($openAction);

            if ($action instanceof PdfDictionary) {
                $this->checkActionType($action, '/OpenAction');
            }
        }

        $aa = $catalog->get('AA');

        if ($aa === null) {
            return;
        }

        $aaDict = $this->doc->resolveObject($aa);

        if (!($aaDict instanceof PdfDictionary)) {
            return;
        }

        foreach ($aaDict->getEntries() as $trigger => $actionRef) {
            $resolved = $this->doc->resolveObject($actionRef);

            if (!($resolved instanceof PdfDictionary)) {
                continue;
            }

            $this->checkActionType($resolved, "/AA/{$trigger}");
        }
    }

    private function checkActionType(PdfDictionary $action, string $location): void
    {
        $s = $action->get('S');

        if (!$s instanceof PdfName) {
            return;
        }

        $type = $s->getValue();

        if (!in_array($type, self::FORBIDDEN_ACTIONS, true)) {
            return;
        }

        $this->error('forbidden-action', "Forbidden action type /{$type} at {$location} (ISO 19005 §6.6.1).");
    }

    private function checkAcroFormFonts(PdfDictionary $catalog): void
    {
        $acroRef = $catalog->get('AcroForm');

        if ($acroRef === null) {
            return;
        }

        $acroForm = $this->doc->resolveObject($acroRef);

        if (!$acroForm instanceof PdfDictionary) {
            return;
        }

        $drRef = $acroForm->get('DR');

        if ($drRef === null) {
            return;
        }

        $dr = $this->doc->resolveObject($drRef);

        if (!$dr instanceof PdfDictionary) {
            return;
        }

        $fontDictRef = $dr->get('Font');

        if ($fontDictRef === null) {
            return;
        }

        $fontDict = $this->doc->resolveObject($fontDictRef);

        if (!$fontDict instanceof PdfDictionary) {
            return;
        }

        foreach ($fontDict->getEntries() as $localName => $fontRef) {
            $font = $this->doc->resolveObject($fontRef);

            if (!($font instanceof PdfDictionary)) {
                continue;
            }

            $this->checkFont($font, $localName, 'AcroForm /DR');
        }
    }

    // -------------------------------------------------------------------------
    // Page-level checks
    // -------------------------------------------------------------------------

    private function checkPages(): void
    {
        try {
            $count = $this->doc->getPageCount();
        } catch (Throwable) {
            return; // Cannot enumerate pages without a valid document catalog.
        }

        for ($i = 0; $i < $count; $i++) {
            try {
                $page = $this->doc->getPage($i);
            } catch (Throwable) { // @codeCoverageIgnore
                $this->error('page.read', 'Page ' . ($i + 1) . ' could not be read.'); // @codeCoverageIgnore

                continue; // @codeCoverageIgnore
            }

            $this->checkPageMediaBox($page, $i);
            $this->checkPageFonts($page, $i);

            if ($this->conformance->part() !== 1) {
                continue;
            }

            $this->checkPageTransparency($page, $i);
        }
    }

    private function checkPageMediaBox(PdfReadPage $page, int $index): void
    {
        if ($page->getDictionary()->get('MediaBox') !== null) {
            return;
        }

        $this->warn(
            'page.mediabox',
            'Page ' . ($index + 1) . ' has no direct /MediaBox entry (may be inherited from page tree).',
        );
    }

    private function checkPageFonts(PdfReadPage $page, int $index): void
    {
        $resources = $page->getResources();
        $fontEntry = $resources->get('Font');

        if ($fontEntry === null) {
            return;
        }

        $fontDict = $this->doc->resolveObject($fontEntry);

        if (!$fontDict instanceof PdfDictionary) {
            return;
        }

        $location = 'page ' . ($index + 1);

        foreach ($fontDict->getEntries() as $localName => $fontRef) {
            $font = $this->doc->resolveObject($fontRef);

            if (!($font instanceof PdfDictionary)) {
                continue;
            }

            $this->checkFont($font, $localName, $location);
        }
    }

    private function checkFont(PdfDictionary $font, string $localName, string $location): void
    {
        // Each unique font object is checked once regardless of how many pages reference it.
        $id = spl_object_id($font);

        if (isset($this->checkedFonts[$id])) {
            return;
        }

        $this->checkedFonts[$id] = true;

        $subtype = $font->get('Subtype');
        $subtypeStr = $subtype instanceof PdfName
            ? $subtype->getValue()
            : null;
        $baseFontObj = $font->get('BaseFont');
        $baseFontName = $baseFontObj instanceof PdfName
            ? $baseFontObj->getValue()
            : $localName;

        match ($subtypeStr) {
            'Type1' => $this->checkType1Font($font, $baseFontName, $location),
            'TrueType' => $this->checkSimpleFont($font, $baseFontName, $location),
            'Type0' => $this->checkType0Font($font, $baseFontName, $location),
            default => null,
        };
    }

    private function checkType1Font(PdfDictionary $font, string $baseFontName, string $location): void
    {
        $fdRef = $font->get('FontDescriptor');

        if ($fdRef === null) {
            if (in_array($baseFontName, self::STANDARD_TYPE1_FONTS, true)) {
                $this->error(
                    'font.not-embedded',
                    "Standard Type 1 font /{$baseFontName} ({$location}) is not embedded. "
                    . 'PDF/A requires all fonts to be embedded (ISO 19005 §6.3.3). '
                    . 'Use an embedded TrueType/OpenType font via useEmbeddedFont() instead.',
                );
            } else {
                $this->error(
                    'font.no-descriptor',
                    "Font /{$baseFontName} ({$location}) has no /FontDescriptor (ISO 19005 §6.3.3).",
                );
            }

            return;
        }

        $fd = $this->doc->resolveObject($fdRef);

        if (!$fd instanceof PdfDictionary) {
            return;
        }

        $embedded = $fd->get('FontFile') ?? $fd->get('FontFile2') ?? $fd->get('FontFile3');

        if ($embedded !== null) {
            return;
        }

        $this->error(
            'font.not-embedded',
            "Type 1 font /{$baseFontName} ({$location}) has a /FontDescriptor but no embedded "
            . 'font program (FontFile/FontFile2/FontFile3) (ISO 19005 §6.3.3).',
        );
    }

    private function checkSimpleFont(PdfDictionary $font, string $baseFontName, string $location): void
    {
        $fdRef = $font->get('FontDescriptor');

        if ($fdRef === null) {
            $this->error(
                'font.no-descriptor',
                "Font /{$baseFontName} ({$location}) has no /FontDescriptor (ISO 19005 §6.3.3).",
            );

            return;
        }

        $fd = $this->doc->resolveObject($fdRef);

        if (!$fd instanceof PdfDictionary) {
            return;
        }

        $embedded = $fd->get('FontFile') ?? $fd->get('FontFile2') ?? $fd->get('FontFile3');

        if ($embedded !== null) {
            return;
        }

        $this->error(
            'font.not-embedded',
            "TrueType font /{$baseFontName} ({$location}) is not embedded (ISO 19005 §6.3.3).",
        );
    }

    private function checkType0Font(PdfDictionary $font, string $baseFontName, string $location): void
    {
        if ($font->get('ToUnicode') === null) {
            $this->warn(
                'font.no-tounicode',
                "Composite font /{$baseFontName} ({$location}) has no /ToUnicode CMap; "
                . 'text search and copy-paste may not work correctly.',
            );
        }

        $descRef = $font->get('DescendantFonts');

        if ($descRef === null) {
            return;
        }

        $descArr = $this->doc->resolveObject($descRef);

        if (!$descArr instanceof PdfArray) {
            return;
        }

        foreach ($descArr->getItems() as $cidRef) {
            $cidFont = $this->doc->resolveObject($cidRef);

            if (!($cidFont instanceof PdfDictionary)) {
                continue;
            }

            $this->checkCidFontEmbedding($cidFont, $baseFontName, $location);
        }
    }

    private function checkCidFontEmbedding(PdfDictionary $cidFont, string $parentName, string $location): void
    {
        $fdRef = $cidFont->get('FontDescriptor');

        if ($fdRef === null) {
            $this->error(
                'font.no-descriptor',
                "CID descendant of /{$parentName} ({$location}) has no /FontDescriptor (ISO 19005 §6.3.3).",
            );

            return;
        }

        $fd = $this->doc->resolveObject($fdRef);

        if (!$fd instanceof PdfDictionary) {
            return;
        }

        $embedded = $fd->get('FontFile') ?? $fd->get('FontFile2') ?? $fd->get('FontFile3');

        if ($embedded !== null) {
            return;
        }

        $this->error(
            'font.not-embedded',
            "CID descendant of /{$parentName} ({$location}) is not embedded (ISO 19005 §6.3.3).",
        );
    }

    // -------------------------------------------------------------------------
    // Transparency checks (PDF/A-1b only)
    // -------------------------------------------------------------------------

    private function checkPageTransparency(PdfReadPage $page, int $index): void
    {
        $pageNum = $index + 1;
        $resources = $page->getResources();

        $extGsRef = $resources->get('ExtGState');

        if ($extGsRef !== null) {
            $extGs = $this->doc->resolveObject($extGsRef);

            if ($extGs instanceof PdfDictionary) {
                foreach ($extGs->getEntries() as $gsName => $gsRef) {
                    $gs = $this->doc->resolveObject($gsRef);

                    if (!$gs instanceof PdfDictionary) {
                        continue;
                    }

                    // Soft mask
                    $smask = $gs->get('SMask');

                    if ($smask !== null) {
                        $resolved = $this->doc->resolveObject($smask);

                        if (!($resolved instanceof PdfName && $resolved->getValue() === 'None')) {
                            $this->error(
                                'transparency.smask',
                                "Page {$pageNum} graphics state /{$gsName} uses a soft mask, "
                                . 'which is forbidden in PDF/A-1b (ISO 19005 §6.2.2).',
                            );
                        }
                    }

                    // Sub-unity opacity
                    foreach (['ca' => 'fill', 'CA' => 'stroke'] as $key => $label) {
                        $alphaObj = $gs->get($key);

                        if ($alphaObj === null) {
                            continue;
                        }

                        $alpha = match (true) {
                            $alphaObj instanceof PdfReal => $alphaObj->getValue(),
                            $alphaObj instanceof PdfInteger => (float) $alphaObj->getValue(),
                            default => null, // @codeCoverageIgnore
                        };

                        if ($alpha === null || $alpha >= 1.0) {
                            continue;
                        }

                        $this->error(
                            'transparency.alpha',
                            "Page {$pageNum} graphics state /{$gsName} sets {$label} opacity "
                            . "to {$alpha}, which is forbidden in PDF/A-1b (ISO 19005 §6.2.2).",
                        );
                    }
                }
            }
        }

        $xobjRef = $resources->get('XObject');

        if ($xobjRef === null) {
            return;
        }

        $xobj = $this->doc->resolveObject($xobjRef);

        if (!$xobj instanceof PdfDictionary) {
            return;
        }

        foreach ($xobj->getEntries() as $xoName => $xoRef) {
            $xo = $this->doc->resolveObject($xoRef);

            if (!$xo instanceof PdfStream) {
                continue;
            }

            $dict = $xo->getDictionary();
            $xoSubtype = $dict->get('Subtype');
            $isImage = $xoSubtype instanceof PdfName && $xoSubtype->getValue() === 'Image';

            if ($isImage) {
                if ($dict->get('SMask') !== null) {
                    $this->error(
                        'transparency.smask',
                        "Page {$pageNum} image XObject /{$xoName} has a soft mask (/SMask); "
                        . 'image transparency is forbidden in PDF/A-1b (ISO 19005 §6.2.2).',
                    );
                }
            } elseif ($dict->get('Group') !== null) {
                $this->error(
                    'transparency.group',
                    "Page {$pageNum} Form XObject /{$xoName} uses a transparency /Group, "
                    . 'which is forbidden in PDF/A-1b (ISO 19005 §6.2.2).',
                );
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function error(string $rule, string $message): void
    {
        $this->issues[] = new PdfAValidationIssue(PdfAIssueLevel::Error, $rule, $message);
    }

    private function warn(string $rule, string $message): void
    {
        $this->issues[] = new PdfAValidationIssue(PdfAIssueLevel::Warning, $rule, $message);
    }
}
