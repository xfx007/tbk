<?php
namespace app\index\controller;

use app\BaseController;
use think\facade\Log;
use think\facade\Request;
require_once '../extend/taobao/TopSdk.php';
class Index extends BaseController
{
    private $appkey = '28302403';
    private $secret = 'a86a06d7f43020bd4a1a16efdf5494a8';
    private $adzone_id = '110143350007';
   	private $c;
    function __construct(){
		$a = new \TopClient;
		$a->appkey = $this->appkey;
		$a->secretKey = $this->secret;
		$this->c = $a;
        // 函数主体，通常用于初始化对象的一些属性
    }
	/*获取订单信息*/
    public function getOrderDetail()
    {
		$req = new \TbkOrderDetailsGetRequest;
		$req->setQueryType("1");
		$req->setPositionIndex("2222_334666");
		$req->setPageSize("20");
		$req->setMemberType("2");
		$req->setTkStatus("12");
		$req->setEndTime("2021-11-04 10:40:27");
		$req->setStartTime("2021-11-04 09:20:27");
		$req->setJumpType("1");
		$req->setPageNo("1");
		$req->setOrderScene("1");
		$resp = $this->c->execute($req);
      		dump($resp);
    }

    public function getUrlId($url)
    {
    	/*   'https://a.m.taobao.com/i606565590015.htm?price=17-68&sourceType=item&detailSharePosition=interactBar&sourceType=item&suid=9ff60348-8bea-4802-9d*/
   /* 	$url = 'https://detail.tmall.com/item.htm?id=617287564682&price=9.81-111.96&sourceType=item&sourceType=item&detailSharePosition=interactBar&suid=7371b4d0-5f4e-4789-8409-f3671cf5f98d&shareUniqueId=13276544174&ut_sk=1.YE0yVfGG8CsDAJg07pa4/4oI_21646297_1635906213655.Copy.1&un=607970205e70cede7a811649f8da3e26&share_crt_v=1&spm=a2159r.13376460.0.0&sp_tk=5LiL5Lus5p2l5LiK5LuW5Lya5L2g5bCx5a2Q5aW55aSp&cpp=1&shareurl=true&short_name=h.fUnMuvh&bxsign=scd1mXI4-iRN4TqfZn5Yk8Z9xcfExAYXumkl48SeH-CJYXG249m8d1NGXbA2jPk_CHPZV5xHgVtwnhXuxVYmlj2pdWNWP5rFz6FN4yLzBJB_pIEU2OdwUHPnRijbSK5p9yJ&sm=85f2f2&app=chrome';*/

        if(strpos($url,'&id=') !== false ){
            preg_match("/&id=(.*?)&/",$url,$id);
        }
		if(empty($id[1]) || strpos($url,'id=') !== false ){
            preg_match("/id=(.*?)&/",$url,$id);
        }
		if(empty($id[1])){
    		$html = $this->getHtmlDate($url);
			preg_match("/\/i(.*?)\.htm/",$html,$id);
		}
		if(empty($id[1])){
			return ""; 
		}
		return $id[1]; 
    }
    /*生成淘口令*/
    public function getKl($url="https://s.click.taobao.com/YI3Uopu",$pict_url,$title="noMeaningValue"){
		$req = new \TbkTpwdCreateRequest;
		$req->setText($title);
        $req->setUrl("https:".$url);
        $req->setLogo("https:".$pict_url);
		$req->setExt("{}");
		$resp = $this->c->execute($req);
        return $resp;
    }
    /*获取id的商品信息 主要获取名称 617287564682*/
    public function getUrlDateById($id="617287564682"){
        $TbkItemInfoGetRequest = new \TbkItemInfoGetRequest;
        $TbkItemInfoGetRequest->setNumIids($id);
        $TbkItemInfoGetRequest->setPlatform("2");
        $TbkItemInfoGetRequestObj = $this->c->execute($TbkItemInfoGetRequest);
        return $TbkItemInfoGetRequestObj;
    }
    /*请求html*/
    public function getHtmlDate($url){
		$html = file_get_contents($url);
		return $html;
    }

    //obj转换数组
    function xml2arr($simxml){
        $simxml = (array)$simxml;//强转
        foreach($simxml as $k => $v){
            if(is_array($v) || is_object($v)){
            $simxml[$k] = $this->xml2arr($v);
            }
        }
        return $simxml;
    }
    public function getTbkDgMaterialOptionalRequest($adzone_id,$id="617287564682",$title,$PageNo=1){

        $TbkDgMaterialOptionalRequest = new \TbkDgMaterialOptionalRequest;
        $TbkDgMaterialOptionalRequest->setAdzoneId($adzone_id);
        $TbkDgMaterialOptionalRequest->setPlatform("2");
        $TbkDgMaterialOptionalRequest->setPageSize("100");
        $TbkDgMaterialOptionalRequest->setQ("$title");
        $TbkDgMaterialOptionalRequest->setPageNo($PageNo);
		$ucrowd_rank_items = new \Ucrowdrankitems;
		$ucrowd_rank_items->item_id=$id;

		$TbkDgMaterialOptionalRequest->setUcrowdRankItems(json_encode($ucrowd_rank_items));
        $getData = $this->c->execute($TbkDgMaterialOptionalRequest);
        if (!isset($getData->result_list->map_data)) {
            return 1001;
        }
        $total_results = ceil($getData->total_results/100);//商品数量
        $getData = $this->xml2arr($getData);
        if (isset($getData['result_list']['map_data'][0])) {
            foreach ($getData['result_list']['map_data'] as $k => $v) {
                if (in_array($id,$v)) {
                    $goodsRes = $v;
                }
            }
        }elseif ($getData['result_list']['map_data']['category_id']) {
            if ($id == $getData['result_list']['map_data']['item_id']) {
                $goodsRes = $getData['result_list']['map_data'];
            }
        } else {
            return 1002;
        }
        $PageNo = $PageNo+1;
        if (isset($goodsRes)) {
	        if (!isset($goodsRes['coupon_share_url'])) {
	            $goodsRes['coupon_share_url'] =  $goodsRes['url'];
	        }
            return $goodsRes;
        }elseif($total_results >= $PageNo){
            return $this->getTbkDgMaterialOptionalRequest($adzone_id,$id,$title,$PageNo);
        }else{
            return 1003;
        }
    }
    public function cese11(){
        $postObj = file_get_contents("php://input");
        $Parm =json_decode($postObj,TRUE);     
		$wxid = $Parm['wxid'];
		$sender = $Parm['sender'];
		$nick = $Parm['nick'];
		$nickSender = $Parm['nickSender'];
		$msg = $Parm['msg'];
    	//获取商品id
    	$id = $this->getUrlId($msg);
    	$goodsDes = $this->getUrlDateById($id);
		Log::record($msg);
    	$title = $goodsDes->results->n_tbk_item->title;
    	$adzone_id = $this->adzone_id;
    	if(strpos($wxid,'@chatroom') !== false && strpos($wxid,'gh_') !== false){
            return  [];
        }
    	//生成推广链
    	$goodsRes  = $this->getTbkDgMaterialOptionalRequest($adzone_id,$id,$title);
    	 if (!isset($goodsRes) || $goodsRes == 1001|| $goodsRes == 1002|| $goodsRes == 1003|| $goodsRes == 1004) {
            //没有优惠券
            $msg = "我好像没找到你的宝贝！";
    		$jsonObj [] = ['type'=>"0",'wxid'=>$wxid,'sender'=>$sender,'msg'=>$msg];
            return json_encode($jsonObj);
        }
    	$url = $goodsRes['coupon_share_url'];
    	$pict_url = $goodsRes['pict_url'];
    	//生成淘口令
    	$tklRes = $this->getKl($url,$pict_url,$title);
    	$modelRes = $this->xml2arr($tklRes);  
        //  商品信息-佣金比率。1550表示15.5%
        $commission = 0;
        if (isset($goodsRes['commission_rate'])) {
            $commission = bcdiv($goodsRes['commission_rate'],10000,4);
        }
        $goodsRes['str'] = '';
        if (!isset($goodsRes['coupon_amount'])) {
            $goodsRes['coupon_amount'] = 0;
        }
        $userfanli = 0.9;
        if(isset($userDataRes)){
            $userfanli = $userDataRes['commission'];
        }
        $price        = bcsub($goodsRes['zk_final_price'],$goodsRes['coupon_amount'],2);
        $price_rate   = bcmul($price,$commission,2);//返利：{$commission_rate}元❤
        $alimama_rate = bcmul($price_rate,0.1,2);//技术费
        $price_rate   = bcsub($price_rate,$alimama_rate,2);
        $userCommission  = bcmul($price_rate,$userfanli,2);
        $msg = "✨{$goodsRes['title']}✨\n原价：{$goodsRes['zk_final_price']}元\n优惠券：{$goodsRes['coupon_amount']}元\n约返利：{$userCommission}元💰\n实付：{$price}元\n復制❤口令：{$modelRes['data']['password_simple']}";
    	Log::record($msg );
    	$jsonObj [] = ['type'=>"0",'wxid'=>$wxid,'sender'=>$sender,'msg'=>$msg];
            return json_encode($jsonObj);
    }
    public function cese1111(){
		$req = new \TbkTpwdConvertRequest;
		$req->setPasswordContent("￥LOg3XvNH4jI￥");
		$req->setAdzoneId($this->adzone_id);
$req->setDx("0");
		$resp = $this->c->execute($req);

		dump($resp);
    }
}
