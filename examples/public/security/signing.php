<?php

declare(strict_types=1);

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Builder\PdfPageSize;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Output\PdfMemoryOutput;
use PhpPdf\Serialization\PdfDocumentSerializer;
use PhpPdf\Signing\PdfDocumentSigner;
use PhpPdf\Signing\PdfSignatureConfig;

/**
 * Creates a self-signed certificate and private key for demonstration.
 * In production, load a certificate issued by a trusted CA instead.
 *
 * @return array{cert: \OpenSSLCertificate, key: \OpenSSLAsymmetricKey}
 */
function createSelfSignedCertificate(): array
{
    $privateKey = openssl_pkey_new([
        'digest_alg' => 'sha256',
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    $csr = openssl_csr_new(
        ['commonName' => 'phppdf Demo Signer', 'countryName' => 'NL'],
        $privateKey,
        ['digest_alg' => 'sha256'],
    );

    $cert = openssl_csr_sign($csr, null, $privateKey, 365, ['digest_alg' => 'sha256']);

    return ['cert' => $cert, 'key' => $privateKey];
}

function generate(): void
{
    ['cert' => $cert, 'key' => $privateKey] = createSelfSignedCertificate();

    $certInfo = openssl_x509_parse($cert);
    $signerName = $certInfo['subject']['CN'] ?? 'Unknown Signer';

    // 1. Build the document with a signature placeholder.
    $document = (new PdfDocumentBuilder())
        ->info(
            (new PdfDocumentInfo())
                ->title('Signed Document')
                ->author($signerName)
                ->subject('Digital signature example'),
        )
        ->prepareForSigning(
            (new PdfSignatureConfig())
                ->name($signerName)
                ->reason('Approved')
                ->location('Amsterdam')
                ->contactInfo('signer@example.com'),
        )
        ->page(static function (PdfPageBuilder $page): void {
            $page
                ->size(...PdfPageSize::A4)
                ->useType1Font('F1', 'Helvetica')
                ->content(static function (PdfContentStreamBuilder $stream): void {
                    $stream
                        ->beginText()
                        ->setFont('F1', 24)
                        ->setTextMatrix(Matrix::translate(72, 720))
                        ->showText('This document is digitally signed.')
                        ->endText();
                });
        })
        ->build();

    // 2. Serialize to memory — the signature field contains only placeholders at
    //    this point; actual bytes and offsets are not yet known.
    $output = new PdfMemoryOutput();
    (new PdfDocumentSerializer($output))->writeDocument($document);

    // 3. Sign: locate the ByteRange and Contents placeholders, compute the
    //    PKCS#7 signature over the covered byte ranges, and patch them in.
    $signer = new PdfDocumentSigner($cert, $privateKey);
    $signedPdf = $signer->sign($output->getContent());

    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($signedPdf));
    header('Content-Disposition: inline; filename="' . basename(__FILE__, '.php') . '.pdf"');
    echo $signedPdf;
}

(static function (): void {
    $autoloader = require __DIR__ . '/../../../vendor/autoload.php';

    setupEnvironment($autoloader);
    generate();
})();
