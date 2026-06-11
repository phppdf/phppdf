<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentEditor;

use PhpPdf\Document\PdfDocument;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObject;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfVersion;

/**
 * Builds minimal PdfDocument fixtures for PdfDocumentEditor unit tests.
 */
trait MinimalDocument
{
    private static function buildDocument(int $pageCount = 1, PdfVersion $version = PdfVersion::PDF_1_7): PdfDocument
    {
        $registry = new PdfObjectRegistry();
        $pageRefs = [];

        for ($i = 0; $i < $pageCount; $i++) {
            $pageRefs[] = $registry->register(new PdfDictionary([
                'MediaBox' => new PdfArray([
                    new PdfReal(0), new PdfReal(0),
                    new PdfReal(595.28), new PdfReal(841.89),
                ]),
                'Type' => new PdfName('Page'),
            ]));
        }

        $pagesRef = $registry->register(new PdfDictionary([
            'Count' => new PdfInteger($pageCount),
            'Kids' => new PdfArray($pageRefs),
            'Type' => new PdfName('Pages'),
        ]));

        foreach ($pageRefs as $pageRef) {
            self::asDictionary($registry->get($pageRef))->set('Parent', $pagesRef);
        }

        $catalogRef = $registry->register(new PdfDictionary([
            'Pages' => $pagesRef,
            'Type' => new PdfName('Catalog'),
        ]));

        return new PdfDocument($registry, $version, $catalogRef, null);
    }

    /**
     * Narrows a PdfObject to a PdfDictionary, asserting it really is one.
     */
    private static function asDictionary(?PdfObject $object): PdfDictionary
    {
        self::assertInstanceOf(PdfDictionary::class, $object);

        return $object;
    }

    /**
     * Narrows a PdfObject to a PdfArray, asserting it really is one.
     */
    private static function asArray(?PdfObject $object): PdfArray
    {
        self::assertInstanceOf(PdfArray::class, $object);

        return $object;
    }

    /**
     * Narrows a PdfObject to a PdfInteger, asserting it really is one.
     */
    private static function asInteger(?PdfObject $object): PdfInteger
    {
        self::assertInstanceOf(PdfInteger::class, $object);

        return $object;
    }

    /**
     * Narrows a nullable PdfObject to a PdfIndirectReference, asserting it really is one.
     */
    private static function asReference(?PdfObject $object): PdfIndirectReference
    {
        self::assertInstanceOf(PdfIndirectReference::class, $object);

        return $object;
    }
}
