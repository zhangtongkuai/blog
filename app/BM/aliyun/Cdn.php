<?php
namespace App\BM\aliyun;

use App\Jobs\BMSendCdnEmail;

class Cdn {
    public static function start($domainName) {
        $client = new \aliyun\cdn\Client(
            [
                'accessKeyId' => 'LTAIaje2eIiJzvBo',
                'accessSecret' => 'iJr2eR2Pz2qctfGoCNhgvQDjDrewKU',
            ]
        );

        //@sec https://help.aliyun.com/document_detail/27158.html
        $package = [
            'Action' => 'StartCdnDomain',
            'DomainName' => $domainName,
        ];
        $response = $client->createRequest($package);
        //dd($response);
        $status = $response->getStatusCode();

        dispatch(new BMSendCdnEmail($status));
    }

    public static function close($domainName) {
        $client = new \aliyun\cdn\Client(
            [
                'accessKeyId' => 'LTAIaje2eIiJzvBo',
                'accessSecret' => 'iJr2eR2Pz2qctfGoCNhgvQDjDrewKU',
            ]
        );

        //@sec https://help.aliyun.com/document_detail/27158.html
        $package = [
            'Action' => 'StopCdnDomain',
            'DomainName' => $domainName,
        ];
        $response = $client->createRequest($package);
        //dd($response);
        $status = $response->getStatusCode();
        
        dispatch(new BMSendCdnEmail($status));
    }
}