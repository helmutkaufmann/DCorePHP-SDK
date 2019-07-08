<?php

namespace DCorePHP\Net\Model\Request;

use DCorePHP\Model\ChainObject;
use DCorePHP\Net\Model\Response\BaseResponse;

class GetNftData extends GetNftDataAbstract
{
    public function __construct(array $ids)
    {
        parent::__construct(
            self::API_GROUP_DATABASE,
            'get_non_fungible_token_data',
            [array_map(static function (ChainObject $id) { return $id->getId(); }, $ids)]
        );
    }

    public static function responseToModel(BaseResponse $response): array
    {
        $nfts = [];
        foreach ($response->getResult() as $rawNft) {
            $nfts[] = parent::resultToModel($rawNft);
        }
        return $nfts;
    }
}