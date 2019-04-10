<?php

namespace DCorePHP\Crypto;

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;
use DCorePHP\DCoreSdk;
use DCorePHP\Exception\ValidationException;
use kornrunner\Secp256k1;
use kornrunner\Serializer\HexSignatureSerializer;
use Mdanter\Ecc\Crypto\Signature\SignatureInterface;
use Mdanter\Ecc\Primitives\PointInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validation;

class ECKeyPair
{
    /** @var PrivateKey */
    private $private;
    /** @var PublicKey */
    private $public;

    /**
     * @param PrivateKey $private
     * @param PublicKey|null $public
     */
    public function __construct(PrivateKey $private = null, PublicKey $public = null)
    {
        $this->private = $private;
        $this->public = $public;
    }

    /**
     * @param string $privateKeyHex
     * @return ECKeyPair
     */
    public static function fromPrivate(string $privateKeyHex): self
    {
        return new self(PrivateKey::fromHex($privateKeyHex));
    }

    /**
     * @param string $privateKeyWif
     * @return ECKeyPair
     * @throws \Exception
     */
    public static function fromBase58(string $privateKeyWif): self
    {
        return new self(PrivateKey::fromWif($privateKeyWif));
    }

    /**
     * @param string $compressedPublicKey
     * @return ECKeyPair
     */
    public static function fromCompressedPublicKey(string $compressedPublicKey): self
    {
        return new self(null, new PublicKey($compressedPublicKey));
    }

    /**
     * @param PointInterface $point
     * @return ECKeyPair
     */
    public static function fromPublicKeyPoint(PointInterface $point): self
    {
        return new self(null, PublicKey::fromPoint($point));
    }

    /**
     * @param int $recId
     * @param SignatureInterface $signature
     * @param string $messageHash
     * @return ECKeyPair|null
     * @throws ValidationException
     */
    public static function recoverFromSignature(int $recId, SignatureInterface $signature, string $messageHash): ?self
    {
        foreach (
            [
                [$recId, [new GreaterThanOrEqual(['value' => 0])]],
                [gmp_sign($signature->getR()), [new GreaterThanOrEqual(['value' => 0])]],
                [gmp_sign($signature->getS()), [new GreaterThanOrEqual(['value' => 0])]],
                [$messageHash, [new NotNull()]]
            ] as $validations
        ) {
            [$subject, $constraints] = $validations;
            if (($violations = Validation::createValidator()->validate($subject, $constraints))->count() > 0) {
                throw new ValidationException($violations);
            }
        }

        $signatureHex = (new HexSignatureSerializer())->serialize($signature);

        $bitcoinECDSA = new BitcoinECDSA();
        $compressedPublicKey = $bitcoinECDSA->getPubKeyWithRS($recId, str_split($signatureHex, 64)[0], str_split($signatureHex, 64)[1], $messageHash);

        return self::fromCompressedPublicKey((string) $compressedPublicKey);
    }

    /**
     * @return PrivateKey
     */
    public function getPrivate(): PrivateKey
    {
        return $this->private;
    }

    /**
     * @return PublicKey
     * @throws \Exception
     */
    public function getPublic(): PublicKey
    {
        if (!$this->public) {
            $this->public = $this->private->toPublicKey();
        }

        return $this->public;
    }

    /**
     * needs to be called in loop until canonical signature is returned
     * transaction needs to be incremented at the beginning on each iteration before this method is called
     *
     * @param string $hexData
     * @return string|null canonical signature on success, null if signature is not canonical
     * @throws \Exception
     */
    public function signature(string $hexData): ?string
    {
        $hash = hash('sha256', pack('H*', DCoreSdk::DCT_CHAIN_ID . $hexData)); // 1cb9ecc48ea039dda1c4626965db67c241486ce886a007903c8d5446465d7c0c
        $derPrivateKey = $this->getPrivate()->toHex();
        $derPublicKey = $this->getPublic()->toCompressedPublicKey(); // compressed public key
        $signature = (new Secp256k1())->sign($hash, $derPrivateKey); // [98168512353566611467237581092075278762442757234040552549754768745652925038257, 44848356081555762428592265712819773562446994169847499152120711725219453447745]

        // possible bug in the hex serializer
        // https://github.com/kornrunner/php-secp256k1/commit/915f0ef1ec748606a1117b171093266de349b058
        // we are using version 0.1.1 because transfer doesn't work with 0.1.2 = after this commit
        $signatureHex = (new HexSignatureSerializer())->serialize($signature); // d90968b241bfdce430bc1dfd15039b08429783d3f1380364294311ca86e0feb16327451e425f07be71768a7fe25d60f232cd12eafe036b9f24b600104415ae41

        $finalRecId = -1;
        $recId = 31;
        do {
            $keyPair = self::recoverFromSignature($recId, $signature, $hash);
            if ($keyPair instanceof self && $keyPair->getPublic()->toCompressedPublicKey() === $derPublicKey) {
                $finalRecId = $recId - 4 - 27;
                break;
            }

            $recId++;
        } while ($recId <= 34);

        if ($finalRecId === -1) {
            throw new \Exception('Could not construct a recoverable keyPair. This should never happen.');
        }

        $canonicalSignature = gmp_strval(gmp_init($finalRecId + 31, 10), 16) . $signatureHex;
        $sigData = str_split($canonicalSignature, 2);

        if ((hexdec($sigData[0]) & 0x80) !== 0 ||
            hexdec($sigData[0]) === 0 ||
            (hexdec($sigData[1]) & 0x80) !== 0 ||
            (hexdec($sigData[32]) & 0x80) !== 0 ||
            hexdec($sigData[32]) === 0 ||
            (hexdec($sigData[33]) & 0x80) !== 0
        ) {
            $canonicalSignature = null;
        }

        return $canonicalSignature;
    }

    /**
     * @param mixed $other
     * @return bool
     */
    public function equals($other): bool
    {
        if ($other instanceof self &&
            $other->getPrivate()->getKey() === $this->getPrivate()->getKey()
        ) {
            return true;
        }

        return false;
    }
}
