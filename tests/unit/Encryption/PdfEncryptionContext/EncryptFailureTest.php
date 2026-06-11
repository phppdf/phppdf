<?php

declare(strict_types=1);

namespace PhpPdf\Encryption {

    use PhpPdf\Encryption\PdfEncryptionContext\EncryptFailureTest;

    /**
     * Override openssl_encrypt in the PhpPdf\Encryption namespace so we can
     * simulate a failure without runkit or uopz. PHP resolves unqualified
     * function calls against the *caller's* namespace first, so this only
     * takes effect for code in PhpPdf\Encryption (e.g. PdfEncryptionContext).
     */
    function openssl_encrypt(
        string $data,
        string $cipher_algo,
        string $passphrase,
        int $options = 0,
        string $iv = '',
        ?string &$tag = null,
        string $aad = '',
        int $tag_length = 16,
    ): string|false {
        if (EncryptFailureTest::isSimulateFailure()) {
            return false;
        }

        return \openssl_encrypt($data, $cipher_algo, $passphrase, $options, $iv); // phpcs:ignore
    }
}

namespace PhpPdf\Encryption\PdfEncryptionContext { // phpcs:ignore

    use PhpPdf\Encryption\PdfEncryptionContext;
    use PHPUnit\Framework\Attributes\CoversClass;
    use PHPUnit\Framework\Attributes\CoversMethod;
    use PHPUnit\Framework\Attributes\Test;
    use PHPUnit\Framework\TestCase;
    use RuntimeException;

    #[CoversClass(PdfEncryptionContext::class)]
    #[CoversMethod(PdfEncryptionContext::class, 'encrypt')]
    final class EncryptFailureTest extends TestCase
    {
        private static bool $simulateFailure = false;

        #[Test]
        public function encryptThrowsRuntimeExceptionWhenOpenSslFails(): void
        {
            // Arrange
            $context = new PdfEncryptionContext(str_repeat("\xAB", 16));
            self::setSimulateFailure(true);

            // Act / Assert
            $this->expectException(RuntimeException::class);
            $context->encrypt('data', 1, 0);
        }

        public static function isSimulateFailure(): bool
        {
            return self::$simulateFailure;
        }

        public static function setSimulateFailure(bool $value): void
        {
            self::$simulateFailure = $value;
        }

        protected function tearDown(): void
        {
            self::setSimulateFailure(false);
        }
    }
}
