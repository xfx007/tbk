<?php
namespace app\index\controller;

use app\BaseController;
use think\facade\Log;
use think\facade\Request;
use app\index\model\Order as OrderMolde;
use app\index\model\User as UserMolde;
use app\index\model\Push as PushMolde;
//订单处理
class Order extends BaseController
{
	public function index(){
		//$list = OrderMolde::find(1);
		$OrderRes = getOrderDetail("2021-11-05 11:10:27","2021-11-05 12:10:27");
		if(empty($OrderRes) || empty($OrderRes['data']['results']['publisher_order_dto'])){
			return "失败";
		}
		$publisher_order_dto[] = $OrderRes['data']['results']['publisher_order_dto'];
		if($OrderRes['data']['results']['publisher_order_dto'][0]){
			$publisher_order_dto= $OrderRes['data']['results']['publisher_order_dto'];
		}

		foreach ($publisher_order_dto as $k => $v) {
			$user=UserMolde::where(['adzone_id'=>$v['adzone_id']])->find()->toArray();
			//dump($user);
			if ($user) {
				$res=OrderMolde::where(['trade_id'=>$v['trade_id']])->find();
				$v['wxid'] = $user['wxid'];
				$v['commission'] = $user['commission'];
				$v['appkey'] = $user['appkey'];
				if(!$res){
					$order=OrderMolde::insert($v);
					if($v['tk_status']==12){
	                  	$data['item_title']=$v['item_title'];//标题
	                  	$data['trade_parent_id']=$v['trade_parent_id'];//腹肌订单
	                  	$data['trade_id']=$v['trade_id'];//订单编号
	                  	$data['alipay_total_price']=$v['alipay_total_price'];//付费
	                   	$str="-----付费成功-{$v['flow_source']}----\n";
	                   	$str.=$data['item_title']."\n";
	                   	$str.='订单号:'.$data['trade_parent_id']."\n";
	                   	$str.='付费金额:'.$data['alipay_total_price']."\n";
	                    $serve = bcdiv($v['alimama_rate'],100,2);//返利：{$commission_rate}元❤
	                    $alimama_rate = bcmul($v['pub_share_pre_fee'],$serve,2);//返利：{$commission_rate}元❤
	                    $pub_share_pre_fee = bcsub($v['pub_share_pre_fee'],$alimama_rate,2);
	                    $money = bcmul($pub_share_pre_fee,$user['commission'],2);
	                   	$str.='约返利:'. $money."\n\n";
	                   	$str.=$this->getmoneyss($user['wxid']);
	                  	$data['msg']=$str;
	                  	$data['wxid']=$user['wxid'];
	                  	$data['status']=1;
	                  	$data['music']=1;
	                  	$data['creation_time']=time();
						PushMolde::insert($data);
						dump($data);
					}
				}
			}
		}
	}
	   public function getmoneyss($wxid="wxid_97zlrrhckxam21"){
        /*提现*/
        $where['wxid'] = $wxid;
        $where['is_out_price'] = 1;
        //本月销售
        $month_start = strtotime(date("Y-m-21"));
        $month_end = strtotime("+1 month -1 seconds", $month_start);
        $month_start_str = date('Y-m-d H:i:s',$month_start);
        $month_end_str = date('Y-m-d H:i:s',$month_end);
        $tbOrderAll = OrderMolde::where($where)->select()->toArray();
        // $where['tk_earning_time'] = array(array('gt',$month_start_str),array('lt',$month_start_str));
        $where['tk_earning_time'] = ['lt',$month_start_str];//小于21的订单（结算后可提现）
        $tbOrderNow = OrderMolde::where($where)->select()->toArray();
        $where['tk_earning_time'] = ['gt',$month_start_str];//大于21日的订单（不可提现）
        $tbOrderOld = OrderMolde::where($where)->select()->toArray();
        $nowMoney  = 0;//现金金额
        $stayMoney = 0;//待结算金额
        $count  = 0;//待结算订单多少个
        $nextMoney  = 0;//下一个月可以提现的金额
        $ymoneyOld = 0;
        $nexCount  = 0;
        //tk_status已拍下：指订单已拍下，但还未付款 已付款：指订单已付款，但还未确认收货 已收货：指订单已确认收货，但商家佣金未支付 已结算：指订单已确认收货，且商家佣金已支付成功 已失效：指订单关闭/订单佣金小于0.01元，订单关闭主要有：1）买家超时未付款； 2）买家付款前，买家/卖家取消了订单；3）订单付款后发起售中退款成功；3：订单结算，11：拍下未付款，12：订单付款， 13：订单失效，14：订单成功

        //type 是否已经返利1返利2未返利3提现中
        //is_out_price 是否退款1未退款2退款

      foreach ($tbOrderAll as $k => $v) {
        	//查询所有的订单的未提现和未退款和付款的订单金额。即为结算的金额
            if ($v['type'] == 2 && $v['is_out_price'] == 1 && $v['tk_status'] ==12) {
            	//推广者赚取佣金后支付给阿里妈妈的技术服务费用的比率
                $serve        = bcdiv($v['alimama_rate'],100,2);
                //付款预估收入=付款金额*提成。指买家付款金额为基数，预估您可能获得的收入。因买家退款等原因，可能与结算预估收入不一致
                $alimama_rate = bcmul($v['pub_share_pre_fee'],$serve,2);
                $pub_share_pre_fee = bcsub($v['pub_share_pre_fee'],$alimama_rate,2);
                $stayMoney += bcmul($pub_share_pre_fee,$v['commission'],2);
                $count++;
            }
        }

        foreach ($tbOrderNow as $k => $v) {
        	//查询所有的订单的提现中和未退款和结算的订单金额。即为提现中的金额
            if ($v['type'] == 3 && $v['is_out_price'] == 1 && $v['tk_status'] ==3) {
            	//推广者赚取佣金后支付给阿里妈妈的技术服务费用的比率
                $serve        = bcdiv($v['alimama_rate'],100,2);
                //付款预估收入=付款金额*提成。指买家付款金额为基数，预估您可能获得的收入。因买家退款等原因，可能与结算预估收入不一致
                $alimama_rate = bcmul($v['pub_share_pre_fee'],$serve,2);//技术费
                $pub_share_pre_fee = bcsub($v['pub_share_pre_fee'],$alimama_rate,2);//淘宝联盟佣金
                $nowMoney += bcmul($pub_share_pre_fee,$v['commission'],2);//我的返利比例
            }
        }
        foreach ($tbOrderOld as $k => $v) {
        	//查询所有的订单的提现中和未退款和结算的订单金额。即为提现中的金额
            if ($v['type'] != 2 && $v['is_out_price'] == 1 && $v['tk_status'] ==3) {
                $serve        = bcdiv($v['alimama_rate'],100,2);//返利：{$commission_rate}元❤
                $alimama_rate = bcmul($v['pub_share_pre_fee'],$serve,2);//返利：{$commission_rate}元❤
                $pub_share_pre_fee = bcsub($v['pub_share_pre_fee'],$alimama_rate,2);
                $nextMoney += bcmul($pub_share_pre_fee,$v['commission'],2);
                $nexCount++;
            }
        }
        return "商品付费成功,收货后返利佣金会自动转账到账户\n本月可提现:{$nowMoney}元\n下月可提现{$nextMoney}元\n当前未收货金额{$stayMoney}元";
        /*提现*/
    }
}