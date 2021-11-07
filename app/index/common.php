<?php
require_once '../extend/taobao/TopSdk.php';
    function sdk(){
        $c = new \TopClient;
        $adzone_id = '110143350007';
        $c->appkey = '28302403';
        $c->secretKey = 'a86a06d7f43020bd4a1a16efdf5494a8';
        return $c ;
    }
    function getOrderDetail($startTime="2021-11-04 09:20:27",$endTime="2021-11-04 10:40:27")
    {
        $req = new \TbkOrderDetailsGetRequest;
        $req->setQueryType("1");
        $req->setPositionIndex("2222_334666");
        $req->setPageSize("100");
        $req->setMemberType("2");
        $req->setTkStatus("12");
        $req->setEndTime($endTime);
        $req->setStartTime($startTime);
        $req->setJumpType("1");
        $req->setPageNo("1");
        $req->setOrderScene("1");
        $resp = sdk()->execute($req);
        return xml2arr($resp);
    }

    //obj转换数组
    function xml2arr($simxml){
        $simxml = (array)$simxml;//强转
        foreach($simxml as $k => $v){
            if(is_array($v) || is_object($v)){
            $simxml[$k] = xml2arr($v);
            }
        }
        return $simxml;
    }
