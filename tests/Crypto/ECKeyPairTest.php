<?php

namespace DCorePHPTests\Crypto;

use DCorePHP\Crypto\ECKeyPair;
use DCorePHP\Crypto\PrivateKey;
use DCorePHP\Crypto\PublicKey;
use DCorePHPTests\DCoreSDKTest;
use kornrunner\Secp256k1;
use PHPUnit\Framework\TestCase;

class ECKeyPairTest extends TestCase
{
    public function testFromPrivate(): void
    {
        $this->assertEquals(
            '5Hxwqx6JJUBYWjQNt8DomTNJ6r6YK8wDJym4CMAH1zGctFyQtzt',
            ECKeyPair::fromPrivate('13a9b612a993aaf5b6f9de0b4a9a373d8ff3f19036bef5d7d51bad55820563eb')->getPrivate()->toWif()
        );
    }

    public function testFromBase58(): void
    {
        $this->assertEquals(
            DCoreSDKTest::PRIVATE_KEY_1,
            ECKeyPair::fromBase58(DCoreSDKTest::PRIVATE_KEY_1)->getPrivate()->toWif()
        );
    }

    public function testFromCompressedpublicKey(): void
    {
        $this->assertEquals(
            'DCT6TjLhr8uESvgtxrbWuXNAN3vcqzBMw5eyEup3PMiD2gnVxeuTb',
            ECKeyPair::fromCompressedPublicKey('02cf2c986e78776c21e5a75d42dd858dfe8ef06cf663ee0e8363db89ad5999d84f')->getPublic()->toAddress()
        );

        $this->assertEquals(
            'DCT5PwcSiigfTPTwubadt85enxMFC18TtVoti3gnTbG7TN9f9R3Fp',
            ECKeyPair::fromCompressedPublicKey('0242e0431837a5843252a0ecfab9565bdb20bdb0fc4c88398455f64589fdc7b93d')->getPublic()->toAddress()
        );
    }

    public function testFromPublicKeyPoint(): void
    {
        $this->assertEquals(
            DCoreSDKTest::PUBLIC_KEY_1,
            ECKeyPair::fromPublicKeyPoint(
                PublicKey::fromWif(DCoreSDKTest::PUBLIC_KEY_1)->toPoint()
            )->getPublic()->toAddress()
        );
    }

    public function testRecoverFromSignature(): void
    {
        $signature = (new Secp256k1())->sign('bae69f774bd176065d1fb9aa7bf9441cfd19775643a22ea18e98b79fdecce15b', PrivateKey::fromWif(DCoreSDKTest::PRIVATE_KEY_1)->toHex());

        $this->assertEquals(
            PublicKey::fromWif(DCoreSDKTest::PUBLIC_KEY_1)->toCompressedPublicKey(),
            ECKeyPair::recoverFromSignature(31, $signature, 'bae69f774bd176065d1fb9aa7bf9441cfd19775643a22ea18e98b79fdecce15b')->getPublic()->toCompressedPublicKey()
        );
    }

    public function testGetGetPrivate(): void
    {
        $this->assertEquals(
            '5Hxwqx6JJUBYWjQNt8DomTNJ6r6YK8wDJym4CMAH1zGctFyQtzt',
            ECKeyPair::fromPrivate('13a9b612a993aaf5b6f9de0b4a9a373d8ff3f19036bef5d7d51bad55820563eb')->getPrivate()->toWif()
        );
    }

    public function testGetPublic(): void
    {
        $this->assertEquals(
            'DCT6TjLhr8uESvgtxrbWuXNAN3vcqzBMw5eyEup3PMiD2gnVxeuTb',
            ECKeyPair::fromCompressedPublicKey('02cf2c986e78776c21e5a75d42dd858dfe8ef06cf663ee0e8363db89ad5999d84f')->getPublic()->toAddress()
        );

        $this->assertEquals(
            DCoreSDKTest::PUBLIC_KEY_1,
            ECKeyPair::fromBase58(DCoreSDKTest::PRIVATE_KEY_1)->getPublic()->toAddress()
        );
    }

    public function testSignature(): void
    {
        $this->assertEquals(
            '1f4e6bf03885ed1ef148e106415b2784a4ba7dfef367099ce534a1d81512bc3c5167773a2bb6f9505a9be62f70fdfb3ec197912622d1f073f37c53f40ac5e646f8',
            ECKeyPair::fromBase58(DCoreSDKTest::PRIVATE_KEY_2)->signature('366507de4ced67d6b55c012720a107000000000000232200000000000201b09aea2900000000000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000c0000000041686f79205048500000', '17401602b201b3c45a3ad98afc6fb458f91f519bd30d1058adf6f2bed66376bc')
        );
    }

    public function testEquals(): void
    {
        $ECKeyPair1 = ECKeyPair::fromBase58(DCoreSDKTest::PRIVATE_KEY_1);
        $ECKeyPair2 = ECKeyPair::fromBase58(DCoreSDKTest::PRIVATE_KEY_1);
        $ECKeyPair3 = ECKeyPair::fromBase58('DCT6TjLhr8uESvgtxrbWuXNAN3vcqzBMw5eyEup3PMiD2gnVxeuTb');

        $this->assertTrue($ECKeyPair1->equals($ECKeyPair2));
        $this->assertFalse($ECKeyPair1->equals($ECKeyPair3));
    }
}
