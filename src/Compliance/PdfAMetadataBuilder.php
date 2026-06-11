<?php

declare(strict_types=1);

namespace PhpPdf\Compliance;

use DateTimeImmutable;
use DateTimeInterface;
use PhpPdf\Document\PdfDocumentInfo;

use const ENT_QUOTES;
use const ENT_XML1;

/**
 * Builds the XMP metadata XML required for PDF/A conformance.
 *
 * The produced XML is suitable for embedding in a PdfXmpMetadataStream and
 * referencing from the document catalog's /Metadata entry. It includes:
 *   - pdfaid namespace (part and conformance level)
 *   - dc namespace (title, creator, subject, format)
 *   - xmp namespace (CreateDate, ModifyDate, CreatorTool)
 *   - pdf namespace (Producer)
 *
 * Document properties are sourced from the optional PdfDocumentInfo passed
 * to build(). When no info is supplied, only the required pdfaid and xmp
 * timestamps are included.
 */
final class PdfAMetadataBuilder
{
    public function build(PdfAConformance $conformance, ?PdfDocumentInfo $info = null): string
    {
        $part = $conformance->part();
        $level = $conformance->conformanceLevel();
        $createDate = $info?->getCreationDate() ?? new DateTimeImmutable();
        $modifyDate = $info?->getModificationDate() ?? $createDate;
        $producer = $info?->getProducer() ?? 'phppdf/phppdf';

        $pdfaidBlock = $this->descriptionBlock(
            'xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/"',
            [
                "pdfaid:conformance" => $level,
                "pdfaid:part" => (string) $part,
            ],
        );

        $dcProperties = ['dc:format' => 'application/pdf'];

        if ($info?->getTitle() !== null) {
            $dcProperties['dc:title'] = $this->langAlt($this->esc($info->getTitle()));
        }

        if ($info?->getAuthor() !== null) {
            $dcProperties['dc:creator'] = $this->seq($this->esc($info->getAuthor()));
        }

        if ($info?->getSubject() !== null) {
            $dcProperties['dc:description'] = $this->langAlt($this->esc($info->getSubject()));
        }

        $dcBlock = $this->descriptionBlock('xmlns:dc="http://purl.org/dc/elements/1.1/"', $dcProperties);

        $xmpBlock = $this->descriptionBlock(
            'xmlns:xmp="http://ns.adobe.com/xap/1.0/"',
            [
                'xmp:CreateDate' => $this->isoDate($createDate),
                'xmp:CreatorTool' => $this->esc($info?->getCreator() ?? $producer),
                'xmp:ModifyDate' => $this->isoDate($modifyDate),
            ],
        );

        $pdfBlock = $this->descriptionBlock(
            'xmlns:pdf="http://ns.adobe.com/pdf/1.3/"',
            ['pdf:Producer' => $this->esc($producer)],
        );

        // UTF-8 BOM in the xpacket begin attribute signals encoding to XMP parsers.
        return implode("\n", [
            '<?xpacket begin="' . "\xEF\xBB\xBF" . '" id="W5M0MpCehiHzreSzNTczkc9d"?>',
            '<x:xmpmeta xmlns:x="adobe:ns:meta/">',
            '  <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">',
            $pdfaidBlock,
            $dcBlock,
            $xmpBlock,
            $pdfBlock,
            '  </rdf:RDF>',
            '</x:xmpmeta>',
            '<?xpacket end="w"?>',
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * Wraps key/value pairs in an rdf:Description element.
     *
     * Values that already contain XML markup (e.g. rdf:Alt/rdf:Seq) are
     * embedded as child elements; plain strings become element text content.
     *
     * @param array<string, string> $properties
     */
    private function descriptionBlock(string $nsAttr, array $properties): string
    {
        $lines = ["    <rdf:Description rdf:about=\"\" {$nsAttr}>"];

        foreach ($properties as $element => $value) {
            if (str_contains($value, '<')) {
                // Structured value — embed as child element.
                $lines[] = "      <{$element}>{$value}</{$element}>";
            } else {
                $lines[] = "      <{$element}>{$value}</{$element}>";
            }
        }

        $lines[] = '    </rdf:Description>';

        return implode("\n", $lines);
    }

    /** Wraps text in an rdf:Alt/rdf:li for language-tagged string properties. */
    private function langAlt(string $text): string
    {
        return "<rdf:Alt><rdf:li xml:lang=\"x-default\">{$text}</rdf:li></rdf:Alt>";
    }

    /** Wraps text in an rdf:Seq/rdf:li for ordered list properties. */
    private function seq(string $text): string
    {
        return "<rdf:Seq><rdf:li>{$text}</rdf:li></rdf:Seq>";
    }

    /** Formats a DateTime as an ISO 8601 string suitable for XMP. */
    private function isoDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:sP');
    }

    /** Escapes XML special characters in plain text values. */
    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
