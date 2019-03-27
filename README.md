# DCorePHP SDK

## Requirements

- [composer](https://getcomposer.org)
- [php ~7.1](http://php.net)
- [php json](http://php.net/manual/en/book.json.php)
- [php bcmath](http://php.net/manual/en/book.bc.php)
- [php gmp](http://php.net/manual/en/book.gmp.php)
- [php openssl](http://php.net/manual/en/book.openssl.php)
- [symfony PropertyAccess component](https://symfony.com/doc/current/components/property_access.html)
- [websocket-php - websocket library](https://github.com/Textalk/websocket-php)
- [stephen-hill/base58php - base58 conversion library](https://github.com/stephen-hill/base58php)
- [kornrunner/php-secp256k1 - secp256k1 library](https://github.com/kornrunner/php-secp256k1)
- [BitcoinPHP/BitcoinECDSA.php - ecdsa library](https://github.com/BitcoinPHP/BitcoinECDSA.php)

## Instalation

composer.json
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/decentfoundation/dcorephp-sdk"
        }
    ],
    "require": {
        "decentfoundation/dcorephp-sdk": "dev-master"
    }
}
```

```bash
composer require decentfoundation/dcorephp-sdk
```

## Usage

### DCore API initialization

```php
$dcoreApi = new \DCorePHP\DCoreApi(
    'http://stagesocket.decentgo.com:8089/',
    'wss://stagesocket.decentgo.com:8090'
);
```

Look at ./src/DCoreApi.php and ./src/Sdk/*Interface.php to see all available methods and their return values.

### Get account

```php
$account = $dcoreApi->getAccountApi()->get(new ChainObject('1.2.34'));
$account = $dcoreApi->getAccountApi()->getByName('Your test account name');
```

### Create account

There are two ways to create account in DCore network: `$dcoreApi->getAccountApi()->registerAccount()` and `$dcoreApi->getAccountApi()->createAccountWithBrainKey()`. 
Recommended way to create account is using `$dcoreApi->getAccountApi()->registerAccount()` method, because it has an option to specify keys. You can use `$dcoreApi->getAccountApi()->createAccountWithBrainKey()`, but keys generated from `$brainkey` for `$publicOwnerKeyWif`, `$publicActiveKeyWif` and `$publicMemoKeyWif` will be the same, which is not recommended for security reasons.

```php
$dcoreApi->getAccountApi()->registerAccount(
    'Your test account name',
    'DCT6MA5TQQ6UbMyMaLPmPXE2Syh5G3ZVhv5SbFedqLPqdFChSeqTz',
    'DCT6MA5TQQ6UbMyMaLPmPXE2Syh5G3ZVhv5SbFedqLPqdFChSeqTz',
    'DCT6MA5TQQ6UbMyMaLPmPXE2Syh5G3ZVhv5SbFedqLPqdFChSeqTz',
    new ChainObject('1.2.34'),
    '5Jd7zdvxXYNdUfnEXt5XokrE3zwJSs734yQ36a1YaqioRTGGLtn'
);
```

### Create transfer

```php
$dcoreApi->getAccountApi()->transfer(
    new Credentials(new ChainObject('1.2.34'), '5Jd7zdvxXYNdUfnEXt5XokrE3zwJSs734yQ36a1YaqioRTGGLtn'),
    '1.2.35',
    (new AssetAmount())->setAmount(1500000),
    'your secret message',
    false
);
```

### Submit content

```php
$dcoreApi->getContentApi()->submitContent(
    null,
    new ChainObject('1.2.34'),
    [],
    'https://decent.ch/', // your content url
    [(new RegionalPrice)->setPrice((new AssetAmount())->setAmount(1000))->setRegion(1)],
    10000,
    '2222222222222222222222222222222222222222',
    [],
    0,
    [],
    '2019-05-28T13:32:34+00:00',
    '1.3.0',
    1000,
    json_encode(['title' => 'Your content title', 'description' => 'Your content description', 'content_type_id' => '1.2.3']),
    '',
    null,
    '5Jd7zdvxXYNdUfnEXt5XokrE3zwJSs734yQ36a1YaqioRTGGLtn'
);
```

### Search content

```php
$contents = $dcoreApi->getContentApi()->findAll(
    'search term',
    '-rating'
);
```

### Buy content

```php
$contents = $dcoreApi->getContentApi()->requestToBuy(
    new ChainObject('1.2.34'),
    'https://decent.ch/', // your content url
    '1.3.0',
    100000000,
    1,
    '5Jd7zdvxXYNdUfnEXt5XokrE3zwJSs734yQ36a1YaqioRTGGLtn'
);
```

### Development requirements & recommendations

- [docker](https://docs.docker.com/install/)
- [docker-compose](https://docs.docker.com/compose/install/)
- [phpunit](https://phpunit.de/)
- [symfony VarDumper component](https://symfony.com/doc/current/components/var_dumper.html)
- [php code sniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [php code sniffer fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
- [php mess detector](https://github.com/phpmd/phpmd)

### PHPStorm configuration

- https://www.jetbrains.com/help/phpstorm/using-php-code-sniffer.html
- https://www.jetbrains.com/help/phpstorm/using-php-cs-fixer.html
- https://www.jetbrains.com/help/phpstorm/using-php-mess-detector.html

## Development & testing

```bash
git clone git@github.com:decentfoundation/dcorephp-sdk.git
cd dcorephp-sdk
docker-compose up -d
docker-compose exec php composer install --dev --prefer-dist --optimize-autoloader
docker-compose exec php ./vendor/bin/phpunit --bootstrap vendor/autoload.php tests
```
