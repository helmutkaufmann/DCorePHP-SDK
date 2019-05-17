<?php

namespace DCorePHPTests\Sdk;

use DCorePHP\Crypto\Credentials;
use DCorePHP\Crypto\ECKeyPair;
use DCorePHP\Exception\ValidationException;
use DCorePHP\Model\Asset\AssetAmount;
use DCorePHP\Model\Content\Content;
use DCorePHP\Model\Content\ContentKeys;
use DCorePHP\Model\Content\ContentObject;
use DCorePHP\Model\ChainObject;
use DCorePHP\Model\Content\SubmitContent;
use DCorePHP\Model\PubKey;
use DCorePHP\Model\RegionalPrice;
use DCorePHP\Net\Model\Request\BaseRequest;
use DCorePHP\Net\Model\Request\BroadcastTransactionWithCallback;
use DCorePHP\Net\Model\Request\Database;
use DCorePHP\Net\Model\Request\GenerateContentKeys;
use DCorePHP\Net\Model\Request\GetChainId;
use DCorePHP\Net\Model\Request\GetContentById;
use DCorePHP\Net\Model\Request\GetContentByURI;
use DCorePHP\Net\Model\Request\GetDynamicGlobalProperties;
use DCorePHP\Net\Model\Request\Login;
use DCorePHP\Net\Model\Request\NetworkBroadcast;
use DCorePHP\Net\Model\Request\RestoreEncryptionKey;
use DCorePHP\Net\Model\Request\SearchContent;
use DCorePHP\Net\Model\Response\BaseResponse;
use DCorePHPTests\DCoreSDKTest;

class ContentApiTest extends DCoreSDKTest
{
    /**
     * @throws \DCorePHP\Exception\InvalidApiCallException
     * @throws \WebSocket\BadOpcodeException
     */
    public function testGenerateKeys(): void
    {
        if ($this->websocketMock) {
            $this->websocketMock
                ->expects($this->exactly(3))
                ->method('send')
                ->withConsecutive(
                    [$this->callback(function(BaseRequest $req) { return $req->setId(1)->toJson() === '{"jsonrpc":"2.0","id":1,"method":"call","params":[1,"login",["",""]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(2)->toJson() === '{"jsonrpc":"2.0","id":2,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(3)->toJson() === '{"jsonrpc":"2.0","id":3,"method":"call","params":[6,"generate_content_keys",[[]]]}'; })]
                )
                ->will($this->onConsecutiveCalls(
                    Login::responseToModel(new BaseResponse('{"id":1,"result":true}')),
                    Database::responseToModel(new BaseResponse('{"id":2,"result":6}')),
                    GenerateContentKeys::responseToModel(new BaseResponse('{"id":3,"result":{"key":"76f4c60775085cbc47ffad3af5040a4a8467a4072913d0b562b2df451b427714","parts":[],"quorum":2}}'))
                ));
        }

        $contentKeys = $this->sdk->getContentApi()->generateKeys([]);

        $this->assertInstanceOf(ContentKeys::class, $contentKeys);
    }

    /**
     * @throws \DCorePHP\Exception\InvalidApiCallException
     * @throws \DCorePHP\Exception\ObjectNotFoundException
     * @throws \WebSocket\BadOpcodeException
     */
    public function testGet(): void
    {
        if ($this->websocketMock) {
            $this->websocketMock
                ->expects($this->exactly(5))
                ->method('send')
                ->withConsecutive(
                    [$this->callback(function(BaseRequest $req) { return $req->setId(1)->toJson() === '{"jsonrpc":"2.0","id":1,"method":"call","params":[1,"login",["",""]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(2)->toJson() === '{"jsonrpc":"2.0","id":2,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(3)->toJson() === '{"jsonrpc":"2.0","id":3,"method":"call","params":[6,"get_content",["https:\/\/www.skrypt.sk\/189791709"]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(4)->toJson() === '{"jsonrpc":"2.0","id":4,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(5)->toJson() === '{"jsonrpc":"2.0","id":5,"method":"call","params":[6,"get_objects",[["2.13.143"]]]}'; })]
                )
                ->will($this->onConsecutiveCalls(
                    Login::responseToModel(new BaseResponse('{"id":1,"result":true}')),
                    Database::responseToModel(new BaseResponse('{"id":2,"result":6}')),
                    GetContentByURI::responseToModel(new BaseResponse('{"id":3,"result":{"id":"2.13.143","author":"1.2.19","co_authors":[["1.2.20",2500],["1.2.21",2500],["1.2.22",2500]],"expiration":"2021-03-31T14:24:53","created":"2019-03-31T14:26:30","price":{"map_price":[[2,{"amount":100000000,"asset_id":"1.3.0"}]]},"size":1,"synopsis":"{\"title\":\"Project proposal\",\"description\":\"description...\",\"content_type_id\":\"1.5.5\"}","URI":"https://www.skrypt.sk/189791709","quorum":0,"key_parts":[],"_hash":"2fbfa189848d2912d123a82d3d88cef3d96e0063","last_proof":[],"is_blocked":false,"AVG_rating":0,"num_of_ratings":0,"times_bought":1,"publishing_fee_escrow":{"amount":0,"asset_id":"1.3.0"},"seeder_price":[]}}')),
                    Database::responseToModel(new BaseResponse('{"id":4,"result":6}')),
                    GetContentById::responseToModel(new BaseResponse('{"id":5,"result":[{"id":"2.13.143","author":"1.2.19","co_authors":[["1.2.20",2500],["1.2.21",2500],["1.2.22",2500]],"expiration":"2021-03-31T14:24:53","created":"2019-03-31T14:26:30","price":{"map_price":[[2,{"amount":100000000,"asset_id":"1.3.0"}]]},"size":1,"synopsis":"{\"title\":\"Project proposal\",\"description\":\"description...\",\"content_type_id\":\"1.5.5\"}","URI":"https://www.skrypt.sk/189791709","quorum":0,"key_parts":[],"_hash":"2fbfa189848d2912d123a82d3d88cef3d96e0063","last_proof":[],"is_blocked":false,"AVG_rating":0,"num_of_ratings":0,"times_bought":1,"publishing_fee_escrow":{"amount":0,"asset_id":"1.3.0"},"seeder_price":[]}]}'))
                ));
        }

        $contentByURI = $this->sdk->getContentApi()->getByURI('https://www.skrypt.sk/189791709');

        /** @var ContentObject $content */
        $content = $this->sdk->getContentApi()->get($contentByURI->getId());

        $this->assertEquals('https://www.skrypt.sk/189791709', $content->getURI());
        $this->assertEquals('1.2.19', $content->getAuthor());
        $this->assertEquals('2fbfa189848d2912d123a82d3d88cef3d96e0063', $content->getHash());
    }

    /**
     * @throws \DCorePHP\Exception\InvalidApiCallException
     * @throws \DCorePHP\Exception\ObjectNotFoundException
     * @throws \WebSocket\BadOpcodeException
     */
    public function testGetByURI(): void
    {
        if ($this->websocketMock) {
            $this->websocketMock
                ->expects($this->exactly(3))
                ->method('send')
                ->withConsecutive(
                    [$this->callback(function(BaseRequest $req) { return $req->setId(1)->toJson() === '{"jsonrpc":"2.0","id":1,"method":"call","params":[1,"login",["",""]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(2)->toJson() === '{"jsonrpc":"2.0","id":2,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(3)->toJson() === '{"jsonrpc":"2.0","id":3,"method":"call","params":[6,"get_content",["https:\/\/www.skrypt.sk\/189791709"]]}'; })]
                )
                ->will($this->onConsecutiveCalls(
                    Login::responseToModel(new BaseResponse('{"id":1,"result":true}')),
                    Database::responseToModel(new BaseResponse('{"id":2,"result":6}')),
                    GetContentByURI::responseToModel(new BaseResponse('{"id":3,"result":{"id":"2.13.143","author":"1.2.19","co_authors":[["1.2.20",2500],["1.2.21",2500],["1.2.22",2500]],"expiration":"2021-03-31T14:24:53","created":"2019-03-31T14:26:30","price":{"map_price":[[2,{"amount":100000000,"asset_id":"1.3.0"}]]},"size":1,"synopsis":"{\"title\":\"Project proposal\",\"description\":\"description...\",\"content_type_id\":\"1.5.5\"}","URI":"https://www.skrypt.sk/189791709","quorum":0,"key_parts":[],"_hash":"2fbfa189848d2912d123a82d3d88cef3d96e0063","last_proof":[],"is_blocked":false,"AVG_rating":0,"num_of_ratings":0,"times_bought":1,"publishing_fee_escrow":{"amount":0,"asset_id":"1.3.0"},"seeder_price":[]}}'))
                ));
        }

        $content = $this->sdk->getContentApi()->getByURI('https://www.skrypt.sk/189791709');

        $this->assertEquals('https://www.skrypt.sk/189791709', $content->getURI());
        $this->assertEquals('1.2.19', $content->getAuthor());
        $this->assertEquals('2fbfa189848d2912d123a82d3d88cef3d96e0063', $content->getHash());
    }

    // TODO: Untested no data
    public function testListAllPublishersRelative(): void
    {
        $sth = $this->sdk->getContentApi()->listAllPublishersRelative('');
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }

    public function testRestoreEncryptionKey(): void
    {
        if ($this->websocketMock) {
            $this->websocketMock
                ->expects($this->exactly(3))
                ->method('send')
                ->withConsecutive(
                    [$this->callback(function(BaseRequest $req) { return $req->setId(1)->toJson() === '{"jsonrpc":"2.0","id":1,"method":"call","params":[1,"login",["",""]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(2)->toJson() === '{"jsonrpc":"2.0","id":2,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(3)->toJson() === '{"jsonrpc":"2.0","id":3,"method":"call","params":[6,"restore_encryption_key",[{"s":"8149734503494312909116126763927194608124629667940168421251424974828815164868905638030541425377704620941193711130535974967507480114755414928915429397074890."},"2.12.3"]]}'; })]
                )
                ->will($this->onConsecutiveCalls(
                    Login::responseToModel(new BaseResponse('{"id":1,"result":true}')),
                    Database::responseToModel(new BaseResponse('{"id":2,"result":6}')),
                    RestoreEncryptionKey::responseToModel(new BaseResponse('{"id":3,"result":"0000000000000000000000000000000000000000000000000000000000000000"}'))
                ));
        }

        $response = $this->sdk->getContentApi()->restoreEncryptionKey(
            (new PubKey())->setPubKey('8149734503494312909116126763927194608124629667940168421251424974828815164868905638030541425377704620941193711130535974967507480114755414928915429397074890'),
            new ChainObject('2.12.3')
        );

        $this->assertEquals('0000000000000000000000000000000000000000000000000000000000000000', $response);
    }

    public function testFindAll(): void
    {
        if ($this->websocketMock) {
            $this->websocketMock
                ->expects($this->exactly(3))
                ->method('send')
                ->withConsecutive(
                    [$this->callback(function(BaseRequest $req) { return $req->setId(1)->toJson() === '{"jsonrpc":"2.0","id":1,"method":"call","params":[1,"login",["",""]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(2)->toJson() === '{"jsonrpc":"2.0","id":2,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(3)->toJson() === '{"jsonrpc":"2.0","id":3,"method":"call","params":[6,"search_content",["","","","","0.0.0","1",100]]}'; })]
                )
                ->will($this->onConsecutiveCalls(
                    Login::responseToModel(new BaseResponse('{"id":1,"result":true}')),
                    Database::responseToModel(new BaseResponse('{"id":2,"result":6}')),
                    SearchContent::responseToModel(new BaseResponse('{"id":3,"result":[{"id":"2.13.834","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1550050109","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-13T09:28:25","times_bought":1},{"id":"2.13.833","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1550049810","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-13T09:23:25","times_bought":1},{"id":"2.13.832","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1550049664","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-13T09:21:05","times_bought":1},{"id":"2.13.831","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1550048924","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-13T09:08:40","times_bought":1},{"id":"2.13.830","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975587","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:46:30","times_bought":1},{"id":"2.13.829","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975568","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:46:05","times_bought":1},{"id":"2.13.828","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975545","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:45:45","times_bought":1},{"id":"2.13.827","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975442","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:44:00","times_bought":0},{"id":"2.13.826","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975424","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:43:45","times_bought":1},{"id":"2.13.825","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975406","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:43:25","times_bought":1},{"id":"2.13.824","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975387","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:43:05","times_bought":1},{"id":"2.13.823","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975355","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:42:35","times_bought":1},{"id":"2.13.822","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975335","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:42:15","times_bought":1},{"id":"2.13.821","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975205","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:40:05","times_bought":1},{"id":"2.13.820","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975180","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:39:40","times_bought":1},{"id":"2.13.819","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975128","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:38:50","times_bought":1},{"id":"2.13.818","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549975046","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:37:25","times_bought":1},{"id":"2.13.817","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974905","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:35:05","times_bought":1},{"id":"2.13.816","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974897","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:34:55","times_bought":1},{"id":"2.13.815","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974892","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:34:55","times_bought":1},{"id":"2.13.814","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974887","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:34:50","times_bought":1},{"id":"2.13.813","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974882","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:34:45","times_bought":1},{"id":"2.13.812","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974878","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:34:40","times_bought":1},{"id":"2.13.811","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974873","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:34:35","times_bought":1},{"id":"2.13.810","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974869","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:34:25","times_bought":1},{"id":"2.13.809","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974864","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:34:25","times_bought":1},{"id":"2.13.808","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974858","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:34:20","times_bought":1},{"id":"2.13.807","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974848","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:34:10","times_bought":1},{"id":"2.13.806","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974143","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:22:20","times_bought":1},{"id":"2.13.805","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974122","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:22:05","times_bought":1},{"id":"2.13.804","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974107","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:21:45","times_bought":1},{"id":"2.13.803","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974009","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:20:10","times_bought":1},{"id":"2.13.802","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549974004","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:20:05","times_bought":1},{"id":"2.13.801","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549973999","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:19:55","times_bought":1},{"id":"2.13.800","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549973972","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:19:30","times_bought":0},{"id":"2.13.799","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549973968","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:19:30","times_bought":0},{"id":"2.13.798","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549973962","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-12T12:19:20","times_bought":0},{"id":"2.13.797","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549898621","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T15:23:40","times_bought":1},{"id":"2.13.796","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549898592","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T15:23:00","times_bought":1},{"id":"2.13.795","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549897995","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T15:13:15","times_bought":1},{"id":"2.13.794","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549896993","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:56:35","times_bought":0},{"id":"2.13.793","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549896982","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:56:15","times_bought":0},{"id":"2.13.792","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549896580","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:49:35","times_bought":1},{"id":"2.13.791","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549896562","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:49:20","times_bought":0},{"id":"2.13.790","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549896486","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:48:00","times_bought":0},{"id":"2.13.789","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549896465","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:47:45","times_bought":0},{"id":"2.13.788","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549896420","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:47:00","times_bought":0},{"id":"2.13.787","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549896359","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:46:00","times_bought":0},{"id":"2.13.786","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549896335","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:45:35","times_bought":0},{"id":"2.13.785","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549893739","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:02:20","times_bought":1},{"id":"2.13.784","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549893732","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:02:10","times_bought":0},{"id":"2.13.783","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549893723","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:02:00","times_bought":0},{"id":"2.13.782","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549893713","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:01:50","times_bought":0},{"id":"2.13.781","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549893642","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:00:40","times_bought":0},{"id":"2.13.780","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":10000000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549893616","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T14:00:15","times_bought":0},{"id":"2.13.779","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549893508","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T13:58:30","times_bought":0},{"id":"2.13.778","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549887030","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T12:10:30","times_bought":1},{"id":"2.13.777","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549886915","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T12:08:35","times_bought":1},{"id":"2.13.776","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549886907","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T12:08:25","times_bought":1},{"id":"2.13.775","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549886899","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T12:08:20","times_bought":1},{"id":"2.13.774","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549886890","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T12:08:10","times_bought":1},{"id":"2.13.773","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549886883","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T12:08:05","times_bought":1},{"id":"2.13.772","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549884341","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T11:25:35","times_bought":1},{"id":"2.13.771","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549884145","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-11T11:22:25","times_bought":1},{"id":"2.13.770","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549636360","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T14:32:40","times_bought":1},{"id":"2.13.769","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549636337","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T14:32:20","times_bought":1},{"id":"2.13.768","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549636314","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T14:31:55","times_bought":1},{"id":"2.13.767","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549634948","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T14:09:10","times_bought":1},{"id":"2.13.766","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549634924","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T14:08:45","times_bought":1},{"id":"2.13.765","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549634902","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T14:08:20","times_bought":1},{"id":"2.13.764","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549634827","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T14:07:05","times_bought":1},{"id":"2.13.763","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549634781","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T14:06:20","times_bought":1},{"id":"2.13.762","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549634760","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T14:06:00","times_bought":0},{"id":"2.13.761","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549634655","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T14:04:15","times_bought":1},{"id":"2.13.760","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549633993","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:53:15","times_bought":1},{"id":"2.13.759","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549633962","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:52:40","times_bought":0},{"id":"2.13.758","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549633937","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:52:15","times_bought":1},{"id":"2.13.757","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549633916","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:51:55","times_bought":1},{"id":"2.13.756","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549633877","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:51:20","times_bought":1},{"id":"2.13.755","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549633851","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:50:50","times_bought":1},{"id":"2.13.754","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549633800","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:50:00","times_bought":1},{"id":"2.13.753","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549633742","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:48:55","times_bought":0},{"id":"2.13.752","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549633481","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:44:40","times_bought":1},{"id":"2.13.751","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549633375","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:42:55","times_bought":1},{"id":"2.13.750","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549632351","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:25:45","times_bought":0},{"id":"2.13.749","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549632045","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:20:45","times_bought":1},{"id":"2.13.748","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549632016","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:20:05","times_bought":1},{"id":"2.13.747","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549631737","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:15:35","times_bought":0},{"id":"2.13.746","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549631684","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:14:40","times_bought":1},{"id":"2.13.745","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549631122","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T13:05:15","times_bought":0},{"id":"2.13.744","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549630613","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T12:56:55","times_bought":1},{"id":"2.13.743","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549630440","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T12:54:00","times_bought":1},{"id":"2.13.742","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549630394","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T12:53:10","times_bought":0},{"id":"2.13.741","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549630138","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T12:48:55","times_bought":1},{"id":"2.13.740","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549630114","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T12:48:35","times_bought":1},{"id":"2.13.739","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549630090","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T12:48:10","times_bought":1},{"id":"2.13.738","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549630066","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T12:47:40","times_bought":1},{"id":"2.13.737","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549630042","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T12:47:20","times_bought":0},{"id":"2.13.736","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549630018","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T12:47:00","times_bought":1},{"id":"2.13.735","author":"u961279ec8b7ae7bd62f304f7c1c3d345","price":{"amount":1000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","status":"Uploaded","URI":"http://decent.ch?testtime=1549629968","_hash":"2222222222222222222222222222222222222222","AVG_rating":0,"size":10000,"expiration":"2019-05-28T13:32:34","created":"2019-02-08T12:46:10","times_bought":1}]}'))
                ));
        }

        $contents = $this->sdk->getContentApi()->findAll();

        $this->assertInternalType('array', $contents);

        foreach ($contents as $content) {
            $this->assertInstanceOf(Content::class, $content);
        }

    }

    /**
     * @throws \Exception
     */
    public function testCreatePurchaseOperation(): void
    {
        if ($this->websocketMock) {
            $this->websocketMock
                ->expects($this->exactly(3))
                ->method('send')
                ->withConsecutive(
                    [$this->callback(function(BaseRequest $req) { return $req->setId(1)->toJson() === '{"jsonrpc":"2.0","id":1,"method":"call","params":[1,"login",["",""]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(2)->toJson() === '{"jsonrpc":"2.0","id":2,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(3)->toJson() === '{"jsonrpc":"2.0","id":3,"method":"call","params":[6,"get_objects",[["2.13.143"]]]}'; })]
                )
                ->will($this->onConsecutiveCalls(
                    Login::responseToModel(new BaseResponse('{"id":1,"result":true}')),
                    Database::responseToModel(new BaseResponse('{"id":2,"result":6}')),
                    GetContentById::responseToModel(new BaseResponse('{"id":3,"result":[{"id":"2.13.143","author":"1.2.19","co_authors":[["1.2.20",2500],["1.2.21",2500],["1.2.22",2500]],"expiration":"2021-03-31T14:24:53","created":"2019-03-31T14:26:30","price":{"map_price":[[2,{"amount":100000000,"asset_id":"1.3.0"}]]},"size":1,"synopsis":"{\"title\":\"Project proposal\",\"description\":\"description...\",\"content_type_id\":\"1.5.5\"}","URI":"https://www.skrypt.sk/189791709","quorum":0,"key_parts":[],"_hash":"2fbfa189848d2912d123a82d3d88cef3d96e0063","last_proof":[],"is_blocked":false,"AVG_rating":0,"num_of_ratings":0,"times_bought":1,"publishing_fee_escrow":{"amount":0,"asset_id":"1.3.0"},"seeder_price":[]}]}'))
                ));
        }

        $credentials = new Credentials(new ChainObject(DCoreSDKTest::ACCOUNT_ID_1), ECKeyPair::fromBase58(DCoreSDKTest::PRIVATE_KEY_1));
        $purchaseOp = $this->sdk->getContentApi()->createPurchaseOperation($credentials, new ChainObject('2.13.143'));

        $this->assertEquals(DCoreSDKTest::ACCOUNT_ID_1, $purchaseOp->getConsumer());
        $this->assertEquals('https://www.skrypt.sk/189791709', $purchaseOp->getUri());
    }

    /**
     * @throws ValidationException
     * @throws \Exception
     */
    public function testPurchase(): void
    {
//        $credentials = new Credentials(new ChainObject('1.2.34'), DCoreSDKTest::PRIVATE_KEY_1);
//        $transConf = $this->sdk->getContentApi()->purchase($credentials, new ChainObject('2.13.974'));
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }

    public function testPurchaseWithUri(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }

    /**
     * @throws \Exception
     */
    public function testCreate(): void
    {
        $randomUri = 'http://decent.ch?testtime=' . time();

        if ($this->websocketMock) {
            $this->websocketMock
                ->expects($this->exactly(11))
                ->method('send')
                ->withConsecutive(
                    [$this->callback(function(BaseRequest $req) { return $req->setId(1)->toJson() === '{"jsonrpc":"2.0","id":1,"method":"call","params":[1,"login",["",""]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(2)->toJson() === '{"jsonrpc":"2.0","id":2,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(3)->toJson() === '{"jsonrpc":"2.0","id":3,"method":"call","params":[6,"get_content",' . json_encode([$req->getParams()[0]]) . ']}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(4)->toJson() === '{"jsonrpc":"2.0","id":4,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(5)->toJson() === '{"jsonrpc":"2.0","id":5,"method":"call","params":[6,"get_dynamic_global_properties",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(6)->toJson() === '{"jsonrpc":"2.0","id":6,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(7)->toJson() === '{"jsonrpc":"2.0","id":7,"method":"call","params":[6,"get_chain_id",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(8)->toJson() === '{"jsonrpc":"2.0","id":8,"method":"call","params":[1,"network_broadcast",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(9)->toJson() === '{"jsonrpc":"2.0","id":9,"method":"call","params":[7,"broadcast_transaction_with_callback",[6,{"extensions":[],"operations":[[20,{"size":10000,"author":"1.2.27","co_authors":[],"URI":' . trim(json_encode([$req->getParams()[1]['operations'][0][1]['URI']]), '[]') . ',"quorum":"0","price":[{"price":{"amount":1000,"asset_id":"1.3.0"},"region":"1"}],"hash":"2222222222222222222222222222222222222222","seeders":[],"key_parts":[],"expiration":"' . $req->getParams()[1]['operations'][0][1]['expiration'] . '","publishing_fee":{"amount":1000000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","fee":{"amount":1000000,"asset_id":"1.3.0"}}]],"ref_block_num":6957,"ref_block_prefix":"2699242012","expiration":"' . $req->getParams()[1]['expiration'] . '","signatures":["' . $req->getParams()[1]['signatures'][0] . '"]}]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(10)->toJson() === '{"jsonrpc":"2.0","id":10,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(11)->toJson() === '{"jsonrpc":"2.0","id":11,"method":"call","params":[6,"get_content",' . json_encode([$req->getParams()[0]]) . ']}'; })]
                )
                ->will($this->onConsecutiveCalls(
                    Login::responseToModel(new BaseResponse('{"id":1,"result":true}')),
                    Database::responseToModel(new BaseResponse('{"id":2,"result":6}')),
                    GetContentByURI::responseToModel(new BaseResponse('{"id":3,"result":null}')),
                    Database::responseToModel(new BaseResponse('{"id":4,"result":6}')),
                    GetDynamicGlobalProperties::responseToModel(new BaseResponse('{"id":5,"result":{"id":"2.1.0","head_block_number":1055533,"head_block_id":"00101b2d1c2ae3a08fa9b4b914b627a3dbdb7177","time":"2019-05-16T13:53:30","current_miner":"1.4.7","next_maintenance_time":"2019-05-17T00:00:00","last_budget_time":"2019-05-16T00:00:00","unspent_fee_budget":20999587,"mined_rewards":"336589000000","miner_budget_from_fees":44333392,"miner_budget_from_rewards":"639249000000","accounts_registered_this_interval":17,"recently_missed_count":0,"current_aslot":1469253,"recent_slots_filled":"297726296795231363828132492270734671359","dynamic_flags":0,"last_irreversible_block_num":1055533}}')),
                    Database::responseToModel(new BaseResponse('{"id":6,"result":6}')),
                    GetChainId::responseToModel(new BaseResponse('{"id":7,"result":"a76a2db75f7a8018d41f2d648c766fdb0ddc79ac77104d243074ebdd5186bfbe"}')),
                    NetworkBroadcast::responseToModel(new BaseResponse('{"id":8,"result":7}')),
                    BroadcastTransactionWithCallback::responseToModel(new BaseResponse('{"method":"notice","params":[6,[{"id":"07a51e27ebd072908c3e4bd28a9fa10bbb75a78e","block_num":1055534,"trx_num":1,"trx":{"ref_block_num":6957,"ref_block_prefix":2699242012,"expiration":"2019-05-16T13:54:02","operations":[[20,{"fee":{"amount":1000000,"asset_id":"1.3.0"},"size":10000,"author":"1.2.27","co_authors":[],"URI":"http://decent.ch?testtime=1558014798","quorum":0,"price":[{"region":1,"price":{"amount":1000,"asset_id":"1.3.0"}}],"hash":"2222222222222222222222222222222222222222","seeders":[],"key_parts":[],"expiration":"2019-05-28T13:32:34","publishing_fee":{"amount":1000000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}"}]],"extensions":[],"signatures":["204631b984f0b95bd99c9a07804177240a6455eb3d489fd77c81d0b494e4d7e9480e401a40c2edca2cf2dd5f399d10e8e743eebd65ad72b51871d368b2d94c629c"],"operation_results":[[0,{}]]}}]]}')),
                    Database::responseToModel(new BaseResponse('{"id":10,"result":6}')),
                    GetContentByURI::responseToModel(new BaseResponse('{"id":11,"result":{"id":"2.13.169","author":"1.2.27","co_authors":[],"expiration":"2019-05-28T13:32:34","created":"2019-04-18T13:40:30","price":{"map_price":[[1,{"amount":1000,"asset_id":"1.3.0"}]]},"size":10000,"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","URI":"'. $randomUri .'","quorum":0,"key_parts":[],"_hash":"2222222222222222222222222222222222222222","last_proof":[],"is_blocked":false,"AVG_rating":0,"num_of_ratings":0,"times_bought":0,"publishing_fee_escrow":{"amount":1000000,"asset_id":"1.3.0"},"seeder_price":[]}}'))
                ));
        }

        $content = new SubmitContent();
        $content
            ->setUri($randomUri)
            ->setCoauthors([])
            ->setCustodyData(null)
            ->setHash('2222222222222222222222222222222222222222')
            ->setKeyParts([])
            ->setSeeders([])
            ->setQuorum(0)
            ->setSize(10000)
            ->setSynopsis(json_encode(['title' => 'Game Title', 'description' => 'Description', 'content_type_id' => '1.2.3']))
            ->setExpiration('2019-05-28T13:32:34+00:00')
            ->setPrice([(new RegionalPrice)->setPrice((new AssetAmount())->setAmount(1000))->setRegion(1)]);

        $credentials = new Credentials(new ChainObject(DCoreSDKTest::ACCOUNT_ID_1), ECKeyPair::fromBase58(DCoreSDKTest::PRIVATE_KEY_1));
        $this->sdk->getContentApi()->create($content, $credentials, (new AssetAmount())->setAmount(1000000)->setAssetId('1.3.0'), (new AssetAmount())->setAmount(1000000)->setAssetId('1.3.0'));

        $submittedContentObject = $this->sdk->getContentApi()->getByURI($randomUri);
        $this->assertEquals( $randomUri, $submittedContentObject->getURI());
        $this->assertEquals(DCoreSDKTest::ACCOUNT_ID_1, $submittedContentObject->getAuthor());
        $this->assertEquals('2222222222222222222222222222222222222222', $submittedContentObject->getHash());
    }

    /**
     * @throws \DCorePHP\Exception\ObjectAlreadyFoundException
     * @throws \DCorePHP\Exception\ObjectNotFoundException
     * @throws ValidationException
     * @throws \Exception
     */
    public function testUpdate(): void
    {
        $uri = 'http://decent.ch?PHPtesttime=' . time();
        $expiration = new \DateTime('+2 day');

        if ($this->websocketMock) {
            $this->websocketMock
                ->expects($this->exactly(19))
                ->method('send')
                ->withConsecutive(
                    [$this->callback(function(BaseRequest $req) { return $req->setId(1)->toJson() === '{"jsonrpc":"2.0","id":1,"method":"call","params":[1,"login",["",""]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(2)->toJson() === '{"jsonrpc":"2.0","id":2,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(3)->toJson() === '{"jsonrpc":"2.0","id":3,"method":"call","params":[6,"get_content",' . json_encode([$req->getParams()[0]]) . ']}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(4)->toJson() === '{"jsonrpc":"2.0","id":4,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(5)->toJson() === '{"jsonrpc":"2.0","id":5,"method":"call","params":[6,"get_dynamic_global_properties",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(6)->toJson() === '{"jsonrpc":"2.0","id":6,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(7)->toJson() === '{"jsonrpc":"2.0","id":7,"method":"call","params":[6,"get_chain_id",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(8)->toJson() === '{"jsonrpc":"2.0","id":8,"method":"call","params":[1,"network_broadcast",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(9)->toJson() === '{"jsonrpc":"2.0","id":9,"method":"call","params":[7,"broadcast_transaction_with_callback",[6,{"extensions":[],"operations":[[20,{"size":10000,"author":"1.2.27","co_authors":[],"URI":' . trim(json_encode([$req->getParams()[1]['operations'][0][1]['URI']]), '[]') . ',"quorum":"0","price":[{"price":{"amount":1000,"asset_id":"1.3.0"},"region":"1"}],"hash":"2222222222222222222222222222222222222222","seeders":[],"key_parts":[],"expiration":"' . $req->getParams()[1]['operations'][0][1]['expiration'] . '","publishing_fee":{"amount":1000000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","fee":{"amount":1000000,"asset_id":"1.3.0"}}]],"ref_block_num":6994,"ref_block_prefix":"3196156583","expiration":"' . $req->getParams()[1]['expiration'] . '","signatures":["' . $req->getParams()[1]['signatures'][0] . '"]}]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(10)->toJson() === '{"jsonrpc":"2.0","id":10,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(11)->toJson() === '{"jsonrpc":"2.0","id":11,"method":"call","params":[6,"get_content",' . json_encode([$req->getParams()[0]]) . ']}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(12)->toJson() === '{"jsonrpc":"2.0","id":12,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(13)->toJson() === '{"jsonrpc":"2.0","id":13,"method":"call","params":[6,"get_dynamic_global_properties",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(14)->toJson() === '{"jsonrpc":"2.0","id":14,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(15)->toJson() === '{"jsonrpc":"2.0","id":15,"method":"call","params":[6,"get_chain_id",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(16)->toJson() === '{"jsonrpc":"2.0","id":16,"method":"call","params":[1,"network_broadcast",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(17)->toJson() === '{"jsonrpc":"2.0","id":17,"method":"call","params":[7,"broadcast_transaction_with_callback",[6,{"extensions":[],"operations":[[20,{"size":10000,"author":"1.2.27","co_authors":[],"URI":' . trim(json_encode([$req->getParams()[1]['operations'][0][1]['URI']]), '[]') . ',"quorum":"0","price":[{"price":{"amount":1000,"asset_id":"1.3.0"},"region":"1"}],"hash":"2222222222222222222222222222222222222222","seeders":[],"key_parts":[],"expiration":"' . $req->getParams()[1]['operations'][0][1]['expiration'] . '","publishing_fee":{"amount":1000001,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title Updated by PHP\",\"description\":\"Description Updated by PHP\",\"content_type_id\":\"1.2.3\"}","fee":{"amount":1000001,"asset_id":"1.3.0"}}]],"ref_block_num":6995,"ref_block_prefix":"2403157060","expiration":"' . $req->getParams()[1]['expiration'] . '","signatures":["' . $req->getParams()[1]['signatures'][0] . '"]}]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(18)->toJson() === '{"jsonrpc":"2.0","id":18,"method":"call","params":[1,"database",[]]}'; })],
                    [$this->callback(function(BaseRequest $req) { return $req->setId(19)->toJson() === '{"jsonrpc":"2.0","id":19,"method":"call","params":[6,"get_content",' . json_encode([$req->getParams()[0]]) . ']}'; })]
                )
                ->will($this->onConsecutiveCalls(
                    Login::responseToModel(new BaseResponse('{"id":1,"result":true}')),
                    Database::responseToModel(new BaseResponse('{"id":2,"result":6}')),
                    GetContentByURI::responseToModel(new BaseResponse('{"id":3,"result":null}')),
                    Database::responseToModel(new BaseResponse('{"id":4,"result":6}')),
                    GetDynamicGlobalProperties::responseToModel(new BaseResponse('{"id":5,"result":{"id":"2.1.0","head_block_number":1055570,"head_block_id":"00101b52a77a81be88745ab49b8d3a5dd213bc05","time":"2019-05-16T13:56:50","current_miner":"1.4.7","next_maintenance_time":"2019-05-17T00:00:00","last_budget_time":"2019-05-16T00:00:00","unspent_fee_budget":20904682,"mined_rewards":"337958000000","miner_budget_from_fees":44333392,"miner_budget_from_rewards":"639249000000","accounts_registered_this_interval":18,"recently_missed_count":0,"current_aslot":1469293,"recent_slots_filled":"255211612614204880954952142629155864575","dynamic_flags":0,"last_irreversible_block_num":1055570}}')),
                    Database::responseToModel(new BaseResponse('{"id":6,"result":6}')),
                    GetChainId::responseToModel(new BaseResponse('{"id":7,"result":"a76a2db75f7a8018d41f2d648c766fdb0ddc79ac77104d243074ebdd5186bfbe"}')),
                    NetworkBroadcast::responseToModel(new BaseResponse('{"id":8,"result":7}')),
                    BroadcastTransactionWithCallback::responseToModel(new BaseResponse('{"method":"notice","params":[6,[{"id":"556fa296fc33524ef601fd11c9839c0af7e5810b","block_num":1055571,"trx_num":0,"trx":{"ref_block_num":6994,"ref_block_prefix":3196156583,"expiration":"2019-05-16T13:57:24","operations":[[20,{"fee":{"amount":1000000,"asset_id":"1.3.0"},"size":10000,"author":"1.2.27","co_authors":[],"URI":"http://decent.ch?PHPtesttime=1558015001","quorum":0,"price":[{"region":1,"price":{"amount":1000,"asset_id":"1.3.0"}}],"hash":"2222222222222222222222222222222222222222","seeders":[],"key_parts":[],"expiration":"2019-05-18T13:56:41","publishing_fee":{"amount":1000000,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}"}]],"extensions":[],"signatures":["1f53c4af346d9978eaefa657225af65d504dd8a4d1940bb1b9a8a258276331e61231f884233855f5399179508b04f9707d4be51d65a98c5835a618c9731b5b5471"],"operation_results":[[0,{}]]}}]]}')),
                    Database::responseToModel(new BaseResponse('{"id":10,"result":6}')),
                    GetContentByURI::responseToModel(new BaseResponse('{"id":11,"result":{"id":"2.13.172","author":"1.2.27","co_authors":[],"expiration":"'. $expiration->format('c') .'","created":"2019-04-18T13:45:25","price":{"map_price":[[1,{"amount":1000,"asset_id":"1.3.0"}]]},"size":10000,"synopsis":"{\"title\":\"Game Title\",\"description\":\"Description\",\"content_type_id\":\"1.2.3\"}","URI":"'.$uri.'","quorum":0,"key_parts":[],"_hash":"2222222222222222222222222222222222222222","last_proof":[],"is_blocked":false,"AVG_rating":0,"num_of_ratings":0,"times_bought":0,"publishing_fee_escrow":{"amount":1000000,"asset_id":"1.3.0"},"seeder_price":[]}}')),
                    Database::responseToModel(new BaseResponse('{"id":12,"result":6}')),
                    GetDynamicGlobalProperties::responseToModel(new BaseResponse('{"id":13,"result":{"id":"2.1.0","head_block_number":1055571,"head_block_id":"00101b5344443d8fdcaf7e399f5a719a637ecd44","time":"2019-05-16T13:57:00","current_miner":"1.4.6","next_maintenance_time":"2019-05-17T00:00:00","last_budget_time":"2019-05-16T00:00:00","unspent_fee_budget":20902117,"mined_rewards":"337995000000","miner_budget_from_fees":44333392,"miner_budget_from_rewards":"639249000000","accounts_registered_this_interval":18,"recently_missed_count":4,"current_aslot":1469295,"recent_slots_filled":"340281716614942596893059355653087035390","dynamic_flags":0,"last_irreversible_block_num":1055571}}')),
                    Database::responseToModel(new BaseResponse('{"id":14,"result":6}')),
                    GetChainId::responseToModel(new BaseResponse('{"id":15,"result":"a76a2db75f7a8018d41f2d648c766fdb0ddc79ac77104d243074ebdd5186bfbe"}')),
                    NetworkBroadcast::responseToModel(new BaseResponse('{"id":16,"result":7}')),
                    BroadcastTransactionWithCallback::responseToModel(new BaseResponse('{"method":"notice","params":[6,[{"id":"782f83e6b11e696da3e7eed6c11b931e1a35553f","block_num":1055572,"trx_num":0,"trx":{"ref_block_num":6995,"ref_block_prefix":2403157060,"expiration":"2019-05-16T13:57:35","operations":[[20,{"fee":{"amount":1000001,"asset_id":"1.3.0"},"size":10000,"author":"1.2.27","co_authors":[],"URI":"http://decent.ch?PHPtesttime=1558015001","quorum":0,"price":[{"region":1,"price":{"amount":1000,"asset_id":"1.3.0"}}],"hash":"2222222222222222222222222222222222222222","seeders":[],"key_parts":[],"expiration":"2019-05-18T13:56:41","publishing_fee":{"amount":1000001,"asset_id":"1.3.0"},"synopsis":"{\"title\":\"Game Title Updated by PHP\",\"description\":\"Description Updated by PHP\",\"content_type_id\":\"1.2.3\"}"}]],"extensions":[],"signatures":["20446cedc64e25000dfd68bbbf7b6a2a3726a6dd8c22abeaa6f19bf4b31f244f6b18416879262b16fc350e870fb67bc7f667c3d5f13164d519f1547002fbbe461b"],"operation_results":[[0,{}]]}}]]}')),
                    Database::responseToModel(new BaseResponse('{"id":18,"result":6}')),
                    GetContentByURI::responseToModel(new BaseResponse('{"id":19,"result":{"id":"2.13.172","author":"1.2.27","co_authors":[],"expiration":"'. $expiration->format('c') .'","created":"2019-04-18T13:45:25","price":{"map_price":[[1,{"amount":1000,"asset_id":"1.3.0"}]]},"size":10000,"synopsis":"{\"title\":\"Game Title Updated by PHP\",\"description\":\"Description Updated by PHP\",\"content_type_id\":\"1.2.3\"}","URI":"'. $uri.'","quorum":0,"key_parts":[],"_hash":"2222222222222222222222222222222222222222","last_proof":[],"is_blocked":false,"AVG_rating":0,"num_of_ratings":0,"times_bought":0,"publishing_fee_escrow":{"amount":1000000,"asset_id":"1.3.0"},"seeder_price":[]}}'))
                ));
        }

        $credentials = new Credentials(new ChainObject(DCoreSDKTest::ACCOUNT_ID_1), ECKeyPair::fromBase58(DCoreSDKTest::PRIVATE_KEY_1));
        $content = new SubmitContent();
        $content
            ->setUri($uri)
            ->setCoauthors([])
            ->setCustodyData(null)
            ->setHash('2222222222222222222222222222222222222222')
            ->setKeyParts([])
            ->setSeeders([])
            ->setQuorum(0)
            ->setSize(10000)
            ->setSynopsis(json_encode(['title' => 'Game Title', 'description' => 'Description', 'content_type_id' => '1.2.3']))
            ->setExpiration($expiration)
            ->setPrice([(new RegionalPrice)->setPrice((new AssetAmount())->setAmount(1000))->setRegion(1)]);

        $this->sdk->getContentApi()->create($content, $credentials, (new AssetAmount())->setAmount(1000000)->setAssetId('1.3.0'), (new AssetAmount())->setAmount(1000000)->setAssetId('1.3.0'));

        $content->setSynopsis(json_encode(['title' => 'Game Title Updated by PHP', 'description' => 'Description Updated by PHP', 'content_type_id' => '1.2.3']));
        $this->sdk->getContentApi()->update($content, $credentials, (new AssetAmount())->setAmount(1000001)->setAssetId('1.3.0'), (new AssetAmount())->setAmount(1000001)->setAssetId('1.3.0'));

        $submittedContentObject = $this->sdk->getContentApi()->getByURI($uri);
        $this->assertEquals( $uri, $submittedContentObject->getURI());
        $this->assertEquals(DCoreSDKTest::ACCOUNT_ID_1, $submittedContentObject->getAuthor());
        $this->assertEquals('Game Title Updated by PHP', $submittedContentObject->getSynopsisDecoded()['title']);
    }

    public function testSubmitContentAsync(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }

    public function testContentCancellation(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }

    public function testDownloadContent(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }

    public function testGetDownloadStatus(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }

    public function testLeaveRatingAndComment(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }

    public function testGenerateEncryptionKey(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }

    public function testSearchUserContent(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }

    public function testGetAuthorAndCoAuthorsByUri(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.'); // @todo
    }
}
