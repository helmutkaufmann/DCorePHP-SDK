<?php

use DCorePHP\Model\Asset\AssetAmount;
use DCorePHP\Model\Memo;
use DCorePHP\Model\ChainObject;
use DCorePHP\Model\Operation\Transfer2;
use DCorePHP\Crypto\Address;
use DCorePHP\Utils\Crypto;
use DCorePHP\Crypto\PrivateKey;
use DCorePHP\Crypto\PublicKey;
use DCorePHPTests\DCoreSDKTest;
use PHPUnit\Framework\TestCase;

class TransferTest extends TestCase
{
    public function testHydrate(): void
    {
        $transfer = new Transfer2();
        $transfer->hydrate([
            'fee' => [
                'amount' => 500000,
                'asset_id' => '1.3.0',
            ],
            'from' => '1.2.687',
            'to' => '1.2.34',
            'amount' => [
                'amount' => 100,
                'asset_id' => '1.3.44',
            ],
            'memo' => [
                'from' => 'DCT1111111111111111111111111111111114T1Anm',
                'to' => 'DCT1111111111111111111111111111111114T1Anm',
                'nonce' => 0,
                'message' => '00000000',
            ],
            'extensions' => [],
        ]);

        $this->assertEquals(500000, $transfer->getFee()->getAmount());
        $this->assertEquals('1.3.0', $transfer->getFee()->getAssetId());
        $this->assertEquals('1.2.687', $transfer->getFrom());
        $this->assertEquals('1.2.34', $transfer->getTo());
        $this->assertEquals(100, $transfer->getAmount()->getAmount());
        $this->assertEquals('1.3.44', $transfer->getAmount()->getAssetId());
        $this->assertNull($transfer->getMemo()->getFrom());
        $this->assertNull($transfer->getMemo()->getTo());
        $this->assertEquals(0, $transfer->getMemo()->getNonce());
        $this->assertEquals('00000000', $transfer->getMemo()->getMessage());
    }

    /**
     * @throws \DCorePHP\Exception\ValidationException
     * @throws Exception
     */
    public function testToBytes(): void
    {
        $senderPrivateKeyWif = DCoreSDKTest::PRIVATE_KEY_1;
        $senderPublicKeyWif = DCoreSDKTest::PUBLIC_KEY_1;
        $recipientPublicKeyWif = DCoreSDKTest::PUBLIC_KEY_2;

        $operation = new Transfer2();
        $operation
            ->setFrom('1.2.34')
            ->setTo('1.2.35')
            ->setAmount(
                (new AssetAmount())
                    ->setAssetId(new ChainObject('1.3.0'))
                    ->setAmount(1)
            )->setFee(
                (new AssetAmount())
                    ->setAssetId(new ChainObject('1.3.0'))
                    ->setAmount(0)
            )->setMemo(
                (new Memo())
                    ->setFrom(Address::decodeCheckNull($senderPublicKeyWif))
                    ->setTo(Address::decodeCheckNull($recipientPublicKeyWif))
                    ->setNonce('735604672334802432')
                    ->setMessage(Crypto::getInstance()->encryptWithChecksum(
                        'hello memo here i am',
                        PrivateKey::fromWif($senderPrivateKeyWif),
                        PublicKey::fromWif($recipientPublicKeyWif),
                        '735604672334802432'
                    ))
            );

        $this->assertEquals(
            '270000000000000000002223000000000002010100000000000000000102c03f8e840c1699fd7808c2bb858e249c688c5be8acf0a0c1c484ab0cfb27f0a802e0ced80260630f641f61f6d6959f32b5c43b1a38be55666b98abfe8bafcc556b002ea2558d64350a204bc2a1ee670302ceddb897c2d351fa0496ff089c934e35e030f8ae4f3f9397a700',
            $operation->toBytes()
        );
    }
}