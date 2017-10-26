<?php
namespace app\controllers;

use Yii;
use yii\data\Pagination;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\db\ActiveRecord;
use app\models\SupplierAccount;
class PayController extends Controller 
{
	public function go_pay(){
		 	$id = intval($_REQUEST['id']);
		 	$act=trim($_REQUEST['act']);
		 	if($id&&$act){
		 		session('pay_order_id',$id);
		 		$redirectUrl = urlencode('http://m.17cct.com/index.php/Pay/pay?act='.$act);  
		 		//授权后重定向的回调链接地址，请使用urlencode对链接进行处理 
				$goUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".C('wx_id')."&redirect_uri=".$redirectUrl."&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
		 		redirect($goUrl);
		 	}
	 }

	//年卡支付流程
	public function pay(){

		header('Content-type: text/html; charset=utf-8');		
		isLogin(U('Pay/go_pay',array('id'=>$order_id)));

		$order_id = intval(session('pay_order_id'));

		//获取Act判断具体执行的付款方法
		$act=$_REQUEST['act'];

		if(!$order_id||!$act){
			$this->error('非法操作',U('User/index'),3);
		}
		$uid = intval(session('uid'));
		import("@.ORG.WxPay_PHP.WxPayPubHelper");

		//使用jsapi接口
		$jsApi = new JsApi_pub();
		//=========步骤1：网页授权获取用户openid============
		//通过code获得openid
		if (!isset($_GET['code']))
		{
			//触发微信返回code码
			$url = $jsApi->createOauthUrlForCode('http://m.17cct.com/index.php/Pay/pay/');
			Header("Location: $url"); 
		}
		else
		{
			//获取code码，以获取openid
		    $code = $_GET['code'];
			$jsApi->setCode($code);
			$openid = $jsApi->getOpenId();
		}

		

		//年卡
		if($act=='card'){
			//检查订单（未付款，有效的）
			$order=M('shop_card_order')->where('id='.$order_id." and user_id=".$uid." and status='1'")->find();
			if (!$order) {
				$this->error('非法订单',U('Card/index'),3);			
			}
			$body=$order['card_name'];//商品名称
			$price=$order['total_price']*100;//交易金额
			$attach=$act.'_'.$order['id'];//传递参数
			$order_sn=$order['order_sn'];//订单号
			$order_id=$order['id'];//订单id
			$this->assign('act',U('Card/pay_back'));
		}else if($act=='route')
		{
			//检查订单（未付款，有效的）
			$order = M('zjy_route_order')->where("is_delete=0  and user_id=".$uid." and id=".$order_id)->find();
			if (!$order) {
				$this->error('非法订单',U('User/index'),3);			
			}
			$body=$order['name'];//商品名称
			$price=$order['total_price']*100;//交易金额
			$attach=$act.'_'.$order['id'];//传递参数
			$order_sn=$order['order_sn'];//订单号
			$order_id=$order['id'];//订单id
			$this->assign('act',U('Route/pay_back'));
		}
			
		
		//设置统一支付接口参数
		$unifiedOrder = new UnifiedOrder_pub();
		$unifiedOrder->setParameter("openid","$openid");//商品描述
		$unifiedOrder->setParameter("body",$body);//商品描述
		//自定义订单号，此处仅作举例
		$timeStamp = time(); 
		$out_trade_no = WxPayConf_pub::APPID."$timeStamp";
		$unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号 
		$unifiedOrder->setParameter("total_fee", $price);//总金额 单位为分  100分为支付一块钱 
		$unifiedOrder->setParameter("notify_url",'http://m.17cct.com/index.php/Pay/notify_url/');//通知地址 
		$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
		$unifiedOrder->setParameter("attach",$attach);


		//非必填参数，商户可根据实际情况选填
		$prepay_id = $unifiedOrder->getPrepayId();
		//=========步骤3：使用jsapi调起支付============
		$jsApi->setPrepayId($prepay_id);
		$jsApiParameters = $jsApi->getParameters();
		$this->assign('jsApiParameters',$jsApiParameters);
		$this->assign('order_id',$order_id);
		$this->assign('order_sn',$order_sn);
		$this->assign('order_sn',$order_sn);

		$this->display();	
		
	}
	
	//年卡异步通知页面
	public function notify_url()
	{
		import("@.ORG.WxPay_PHP.WxPayPubHelper");
		import('log_',APP_PATH.'Lib/ORG/WxPay_PHP','.php');
		//$data=$GLOBALS["HTTP_RAW_POST_DATA"];
		//使用通用通知接口
		$notify = new Notify_pub();
		//存储微信的回调
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];	
		$notify->saveData($xml);
		$Common = new Common_util_pub();
		$xml_array_data=$Common->xmlToArray($xml);
		if($xml_array_data['result_code']=='SUCCESS'){//返回数据成功
			session('pay_order_id',null);
			$attach=explode('_',$xml_array_data['attach']);
			if($attach[0]=='card'){
				//订单信息
				$order = M('shop_card_order')->where(array('id'=>intval($attach[1])))->find();
				//means_of_payment 支付工具，0为未使用支付，1为支付宝支付，2为微信支付
				if($order['status']!=2){
					//更新订单信息
					$data = array('status'=>'2','means_of_payment'=>'2','outer_notice_sn'=>$xml_array_data['transaction_id'],'pay_time'=>time());						
					$r = M('shop_card_order')->where(array('id'=>$order['id']))->save($data);
					if($r){
						M('shop_card')->where('id='.$order['card_id'])->setInc('buy_count'); 
					}
					// 生成服务码
				} 	
			}
			else if($attach[0]=='route'){
				//订单信息
				$order = M('zjy_route_order')->where(array('id'=>intval($attach[1])))->find();
				//means_of_payment 支付工具，0为未使用支付，1为支付宝支付，2为微信支付
				if($order['status']!='2'){
					//更新订单信息
					$data = array('status'=>'2','means_of_payment'=>'2','outer_notice_sn'=>$xml_array_data['transaction_id'],'pay_time'=>time());						
					$r = M('zjy_route_order')->where(array('id'=>$order['id']))->save($data);
					M('zjy_route')->where('id='.$order['route_id'])->setInc('sell_count'); 
					if($r){
							//生成验证码
							$route_data=add_route_coupon($order['id'],$order['route_id'],$order['user_id'],$order['agency_id']);
							if($route_data){
								//发送短信
							}
					}
					
				}
			}	

		}
		//验证签名，并回应微信。
		//对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
		//微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
		//尽可能提高通知的成功率，但微信不保证通知最终能成功。
		if($notify->checkSign() == FALSE){
			$notify->setReturnParameter("return_code","FAIL");//返回状态码
			$notify->setReturnParameter("return_msg","签名失败");//返回信息
		}else{
			$notify->setReturnParameter("return_code","SUCCESS");//设置返回码
		}
		$returnXml = $notify->returnXml();
		//echo $returnXml;
		
		//==商户根据实际情况设置相应的处理流程，此处仅作举例=======
		
		//以log文件形式记录回调信息
		$log_ = new Log_();
		$log_name=APP_PATH."Lib/ORG/WxPay_PHP/notify_url.log";//log文件路径
		$log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");

		if($notify->checkSign() == TRUE)
		{
			if ($notify->data["return_code"] == "FAIL") {
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
			}
			elseif($notify->data["result_code"] == "FAIL"){
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【业务出错】:\n".$xml."\n");
			}
			else{
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【支付成功】:\n".$xml."\n");
			}		
		}	
	}

	//客户预约订单支付
	public function erp_go_pay(){
		 	$id = intval($_REQUEST['id']);		 	
		 	if($id){
		 		session('erp_pay_order_id',$id);
		 		$redirectUrl = urlencode('http://m.17cct.com/index.php/Pay/erp_order_pay');  
		 		//授权后重定向的回调链接地址，请使用urlencode对链接进行处理 
				$goUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".C('wx_id')."&redirect_uri=".$redirectUrl."&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
		 		redirect($goUrl);
		 	}
	 }

	public function erp_order_pay()
	{		
		header('Content-type: text/html; charset=utf-8');

		$order_id = intval(session('erp_pay_order_id'));

		isLogin(U('Pay/erp_order_pay',array('id'=>$order_id)));

		$user_info=session('user_info');

		//检查订单（未付款，有效的）		
		$order = M('erp_order')->field('fw_erp_order.order_sn,fw_erp_order.total_price,fw_erp_order.id,feoi.project_name')->join('fw_erp_order_item as feoi on feoi.order_id=fw_erp_order.id')->where("fw_erp_order.type='3' and fw_erp_order.pay_status='0' and fw_erp_order.wx_user_id=".$user_info['id']." and fw_erp_order.id=".$order_id)->find();

		if (!$order||$order['total_price']<=0||!$order_id) {			
			$this->error('非法订单',U('User/index'),3);			
		}	
		import("@.ORG.WxPay_PHP.WxPayPubHelper");
		//使用jsapi接口
		$jsApi = new JsApi_pub();
		//=========步骤1：网页授权获取用户openid============
		//通过code获得openid
		if (!isset($_GET['code']))
		{
			//触发微信返回code码
			$url = $jsApi->createOauthUrlForCode(WxPayConf_pub::JS_API_CALL_URL);
			Header("Location: $url"); 
		}
		else
		{
			//获取code码，以获取openid
		    $code = $_GET['code'];
			$jsApi->setCode($code);
			$openid = $jsApi->getOpenId();
		}
		//=========步骤2：使用统一支付接口，获取prepay_id============
		//使用统一支付接口
		$unifiedOrder = new UnifiedOrder_pub();
		//设置统一支付接口参数
		$unifiedOrder->setParameter("openid","$openid");//商品描述
		$unifiedOrder->setParameter("body",$order['project_name']);//商品描述
		//自定义订单号，此处仅作举例
		$timeStamp = time();
		$out_trade_no = WxPayConf_pub::APPID."$timeStamp";
		//$out_trade_no=$order['order_sn'];
		$unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号 
		$unifiedOrder->setParameter("total_fee", $order['total_price']*100);//总金额 单位为分  100分为支付一块钱 $order['total_price']*100
		$unifiedOrder->setParameter("notify_url",'http://m.17cct.com/index.php/Pay/erp_notify_url/');//通知地址 
		$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
		$unifiedOrder->setParameter("attach",$order['id']);
		//非必填参数，商户可根据实际情况选填		
		$prepay_id = $unifiedOrder->getPrepayId();
		//=========步骤3：使用jsapi调起支付============
		$jsApi->setPrepayId($prepay_id);
		$jsApiParameters = $jsApi->getParameters();
		$this->assign('jsApiParameters',$jsApiParameters);
		$this->assign('order_id',$order['id']);
		$this->assign('order_sn',$order['order_sn']);
		$this->display();	
	}

	//erp预约
	public function erp_notify_url()
	{
		import("@.ORG.WxPay_PHP.WxPayPubHelper");
		import('log_',APP_PATH.'Lib/ORG/WxPay_PHP','.php');
		//$data=$GLOBALS["HTTP_RAW_POST_DATA"];
		//使用通用通知接口
		$notify = new Notify_pub();
		//存储微信的回调
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];	
		$notify->saveData($xml);
		$Common = new Common_util_pub();
		$xml_array_data=$Common->xmlToArray($xml);
		if($xml_array_data['result_code']=='SUCCESS'){//返回数据成功
			session('erp_pay_order_id',null);
			$attach=$xml_array_data['attach'];
		
				//订单信息
				$order = M('erp_order')->field('fw_erp_order.id,fw_erp_order.total_price,fw_erp_order.pay_status,fw_erp_order.wx_user_id,fw_erp_order.user_id,fw_erp_order.location_id,fw_erp_order.type,feoi.project_name,feoi.project_id')->join('fw_erp_order_item as feoi on feoi.order_id=fw_erp_order.id')->where('fw_erp_order.id='.intval($attach))->find();
				//means_of_payment 支付方式，0为待结算，1为现金，2为刷卡，3为会员卡余额购买,4为支付宝,5为微信,6为E卡
				if($order['pay_status']=='0'&&$order['type']=='3'){
					//更新订单信息
					$data = array('means_of_payment'=>'5','outer_notice_sn'=>$xml_array_data['transaction_id'],'pay_time'=>time(),'pay_status'=>'1','pay_amount'=>$order['total_price']);						
					$r = M('erp_order')->where(array('id'=>$order['id']))->save($data);
					if($r){
						M('erp_goods')->where('id='.$order['project_id'])->setInc('buy_count'); 
					}

					$order_deal['order_id']=$order['id'];
					$order_deal['price']=$order['total_price'];
					$order_deal['mean_of_payment']='5';
					$order_deal['pay_time']=time();
					M('erp_order_deal')->add($order_deal);

					$store = M('supplier_location')->field('name,tel,mobile,address,supplier_id')->where(array('id'=>$order['location_id']))->find();
					$store['tel'] = empty($store['tel']) ? $store['mobile'] : $store['tel'] ;
					// 生成服务码
					$coupon['is_del']=0;
					$coupon['member_id']=$order['user_id'];
					$coupon['user_id']=$order['wx_user_id'];
					$coupon['order_id']=$order['id'];
					$coupon['goods_id']=$order['project_id'];
					$coupon['supplier_id']=$store['supplier_id'];
					$coupon['location_id']=$order['location_id'];
					$coupon['add_time']=time();
					$coupon['sn']=rand(100000000,9999999999);
					while(!M('erp_coupon')->add($coupon))
					{
						$coupon['sn'] = rand(100000000,9999999999);
					}

					$u=M('user')->where('id='.$order['wx_user_id'])->find();

					$userMsg['order_id']       = $order['id'];
					$userMsg['user_true_name'] = $u['true_name'];
					$userMsg['user_mobile']    = $u['mobile'];
					$userMsg['user_wxid']      = $u['wxid'];
					$userMsg['deal_id']        = $order['id'];
					$userMsg['deal_name']      = $order['project_name'];
					$userMsg['deal_tpye']      = '3';
					$userMsg['deal_attr']      = '';
					$userMsg['coupon'] 	       = $coupon['sn'];
					$userMsg['store_tel']      = $store['tel'];
					$userMsg['store_name']     = $store['name'];
					$userMsg['store_address']  = $store['address'];
					$userMsg['send_type']  = 'erp';
					paySuccessSendMsg('user',$userMsg);

					//发送 商家 短信 微信
					$storeMsg['user_true_name'] = $u['true_name'];
					$storeMsg['user_mobile']    = $u['mobile'];
					$storeMsg['deal_name']   	= $order['project_name'];
					$storeMsg['deal_tpye'] 		= '3';
					$storeMsg['store_mobile']   = $store['mobile'];
					//paySuccessSendMsg('store',$storeMsg);	

					
				} 

		}
		//验证签名，并回应微信。
		//对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
		//微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
		//尽可能提高通知的成功率，但微信不保证通知最终能成功。
		if($notify->checkSign() == FALSE){
			$notify->setReturnParameter("return_code","FAIL");//返回状态码
			$notify->setReturnParameter("return_msg","签名失败");//返回信息
		}else{
			$notify->setReturnParameter("return_code","SUCCESS");//设置返回码
		}
		$returnXml = $notify->returnXml();
		//echo $returnXml;
		
		//==商户根据实际情况设置相应的处理流程，此处仅作举例=======
		
		//以log文件形式记录回调信息
		$log_ = new Log_();
		$log_name=APP_PATH."Lib/ORG/WxPay_PHP/notify_url.log";//log文件路径
		$log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");

		if($notify->checkSign() == TRUE)
		{
			if ($notify->data["return_code"] == "FAIL") {
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
			}
			elseif($notify->data["result_code"] == "FAIL"){
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【业务出错】:\n".$xml."\n");
			}
			else{
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【支付成功】:\n".$xml."\n");
			}		
		}	
	}



	//客户预约订单支付
	public function package_go_pay(){
		 	$id = intval($_REQUEST['id']);		 	
		 	if($id){
		 		session('package_pay_order_id',$id);
		 		$redirectUrl = urlencode('http://m.17cct.com/index.php/Pay/package_order_pay');  
		 		//授权后重定向的回调链接地址，请使用urlencode对链接进行处理 
				$goUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".C('wx_id')."&redirect_uri=".$redirectUrl."&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
		 		redirect($goUrl);
		 	}
	 }

	public function package_order_pay()
	{		
		header('Content-type: text/html; charset=utf-8');

		$order_id = intval(session('package_pay_order_id'));

		isLogin(U('Pay/package_go_pay',array('id'=>$order_id)));

		$user_info=session('user_info');

		//检查订单（未付款，有效的）		
		$order = M('back_package_order')->field('total_price,name,id,order_sn')->where('status=0 and user_id='.$user_info['id']." and id=".$order_id)->find();

		if (!$order||$order['total_price']<=0||!$order_id) {			
			$this->error('非法订单',U('User/index'),3);			
		}	
		import("@.ORG.WxPay_PHP.WxPayPubHelper");
		//使用jsapi接口
		$jsApi = new JsApi_pub();
		//=========步骤1：网页授权获取用户openid============
		//通过code获得openid
		if (!isset($_GET['code']))
		{
			//触发微信返回code码
			$url = $jsApi->createOauthUrlForCode(WxPayConf_pub::JS_API_CALL_URL);
			Header("Location: $url"); 
		}
		else
		{
			//获取code码，以获取openid
		    $code = $_GET['code'];
			$jsApi->setCode($code);
			$openid = $jsApi->getOpenId();
		}
		if(!$openid){
			$this->error('网络繁忙，请稍后重试',U('Package/order_info',array('id'=>$order_id)),2);	
		}
		//=========步骤2：使用统一支付接口，获取prepay_id============
		//使用统一支付接口
		$unifiedOrder = new UnifiedOrder_pub();
		//设置统一支付接口参数
		$unifiedOrder->setParameter("openid","$openid");//商品描述
		$unifiedOrder->setParameter("body",$order['name']);//商品名称
		//自定义订单号，此处仅作举例
		$timeStamp = time();
		$out_trade_no = WxPayConf_pub::APPID."$timeStamp";
		//$out_trade_no=$order['order_sn'];
		$unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号 
		$unifiedOrder->setParameter("total_fee", $order['total_price']*100);//总金额 单位为分  100分为支付一块钱 $order['total_price']*100
		$unifiedOrder->setParameter("notify_url",'http://m.17cct.com/index.php/Pay/package_notify_url/');//通知地址 
		$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
		$unifiedOrder->setParameter("attach",$order['id']);
		//非必填参数，商户可根据实际情况选填		
		$prepay_id = $unifiedOrder->getPrepayId();
		//=========步骤3：使用jsapi调起支付============
		$jsApi->setPrepayId($prepay_id);
		$jsApiParameters = $jsApi->getParameters();
		$this->assign('jsApiParameters',$jsApiParameters);
		$this->assign('order_id',$order['id']);
		$this->assign('order_sn',$order['order_sn']);
		$this->display();	
	}

	//全返套餐
	public function package_notify_url()
	{
		import("@.ORG.WxPay_PHP.WxPayPubHelper");
		import('log_',APP_PATH.'Lib/ORG/WxPay_PHP','.php');
		//$data=$GLOBALS["HTTP_RAW_POST_DATA"];
		//使用通用通知接口
		$notify = new Notify_pub();
		//存储微信的回调
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];	
		$notify->saveData($xml);
		$Common = new Common_util_pub();
		$xml_array_data=$Common->xmlToArray($xml);
		if($xml_array_data['result_code']=='SUCCESS'){//返回数据成功
			session('erp_pay_order_id',null);
			$attach=$xml_array_data['attach'];
		
				//订单信息
				//$order = M('erp_order')->field('fw_erp_order.id,fw_erp_order.total_price,fw_erp_order.pay_status,fw_erp_order.wx_user_id,fw_erp_order.user_id,fw_erp_order.location_id,fw_erp_order.type,feoi.project_name,feoi.project_id')->join('fw_erp_order_item as feoi on feoi.order_id=fw_erp_order.id')->where('fw_erp_order.id='.intval($attach))->find();
				$order = M('back_package_order')->where(" id=".intval($attach))->find();
				//means_of_payment 支付方式，0为待结算，1为现金，2为刷卡，3为会员卡余额购买,4为支付宝,5为微信,6为E卡
				if($order['pay_time']==0&&$order['status']==0){
					
					//更新订单信息
					$data = array('outer_notice_sn'=>$xml_array_data['transaction_id'],'pay_time'=>time(),'status'=>'1');						
					$r = M('back_package_order')->where(array('id'=>$order['id']))->save($data);
					

					// $order_deal['order_id']=$order['id'];
					// $order_deal['price']=$order['total_price'];
					// $order_deal['mean_of_payment']='5';
					// $order_deal['pay_time']=time();
					// M('erp_order_deal')->add($order_deal);

					// $store = M('supplier_location')->field('name,tel,mobile,address,supplier_id')->where(array('id'=>$order['location_id']))->find();
					// $store['tel'] = empty($store['tel']) ? $store['mobile'] : $store['tel'] ;
					// // 生成服务码
					// $coupon['is_del']=0;
					// $coupon['member_id']=$order['user_id'];
					// $coupon['user_id']=$order['wx_user_id'];
					// $coupon['order_id']=$order['id'];
					// $coupon['goods_id']=$order['project_id'];
					// $coupon['supplier_id']=$store['supplier_id'];
					// $coupon['location_id']=$order['location_id'];
					// $coupon['add_time']=time();
					// $coupon['sn']=rand(100000000,9999999999);
					// while(!M('erp_coupon')->add($coupon))
					// {
					// 	$coupon['sn'] = rand(100000000,9999999999);
					// }

					// $u=M('user')->where('id='.$order['wx_user_id'])->find();

					// $userMsg['order_id']       = $order['id'];
					// $userMsg['user_true_name'] = $u['true_name'];
					// $userMsg['user_mobile']    = $u['mobile'];
					// $userMsg['user_wxid']      = $u['wxid'];
					// $userMsg['deal_id']        = $order['id'];
					// $userMsg['deal_name']      = $order['project_name'];
					// $userMsg['deal_tpye']      = '3';
					// $userMsg['deal_attr']      = '';
					// $userMsg['coupon'] 	       = $coupon['sn'];
					// $userMsg['store_tel']      = $store['tel'];
					// $userMsg['store_name']     = $store['name'];
					// $userMsg['store_address']  = $store['address'];
					// $userMsg['send_type']  = 'erp';
					// paySuccessSendMsg('user',$userMsg);

					// //发送 商家 短信 微信
					// $storeMsg['user_true_name'] = $u['true_name'];
					// $storeMsg['user_mobile']    = $u['mobile'];
					// $storeMsg['deal_name']   	= $order['project_name'];
					// $storeMsg['deal_tpye'] 		= '3';
					// $storeMsg['store_mobile']   = $store['mobile'];
					//paySuccessSendMsg('store',$storeMsg);	

					
				} 

		}
		//验证签名，并回应微信。
		//对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
		//微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
		//尽可能提高通知的成功率，但微信不保证通知最终能成功。
		if($notify->checkSign() == FALSE){
			$notify->setReturnParameter("return_code","FAIL");//返回状态码
			$notify->setReturnParameter("return_msg","签名失败");//返回信息
		}else{
			$notify->setReturnParameter("return_code","SUCCESS");//设置返回码
		}
		$returnXml = $notify->returnXml();
		//echo $returnXml;
		
		//==商户根据实际情况设置相应的处理流程，此处仅作举例=======
		
		//以log文件形式记录回调信息
		$log_ = new Log_();
		$log_name=APP_PATH."Lib/ORG/WxPay_PHP/notify_url.log";//log文件路径
		$log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");

		if($notify->checkSign() == TRUE)
		{
			if ($notify->data["return_code"] == "FAIL") {
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
			}
			elseif($notify->data["result_code"] == "FAIL"){
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【业务出错】:\n".$xml."\n");
			}
			else{
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【支付成功】:\n".$xml."\n");
			}		
		}	
	}

	

	//代理订单支付
	public function agent_go_pay(){
		 	$id = intval($_REQUEST['id']);		 	
		 	if($id){
		 		session('agent_pay_order_id',$id);
		 		$redirectUrl = urlencode('http://m.17cct.com/index.php/Pay/agent_order_pay');  
		 		//授权后重定向的回调链接地址，请使用urlencode对链接进行处理 
				$goUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".C('wx_id')."&redirect_uri=".$redirectUrl."&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
		 		redirect($goUrl);
		 	}
	 }

	public function agent_order_pay()
	{		
		header('Content-type: text/html; charset=utf-8');

		$order_id = intval(session('agent_pay_order_id'));


		//检查订单（未付款，有效的）		
		$order = M('agent_order')->field('total_price,name,id,order_sn')->where('pay_status=0  and id='.$order_id)->find();
		//and open_id="'.session('oprate_opend_id').'"
		
		if (!$order||$order['total_price']<=0||!$order_id) {			
			$this->error('非法订单',U('Agent/index'),3);			
		}	
		import("@.ORG.WxPay_PHP.WxPayPubHelper");
		//使用jsapi接口
		$jsApi = new JsApi_pub();
		//=========步骤1：网页授权获取用户openid============
		//通过code获得openid
		if (!isset($_GET['code']))
		{
			//触发微信返回code码
			$url = $jsApi->createOauthUrlForCode(WxPayConf_pub::JS_API_CALL_URL);
			Header("Location: $url"); 
		}
		else
		{
			//获取code码，以获取openid
		    $code = $_GET['code'];
			$jsApi->setCode($code);
			$openid = $jsApi->getOpenId();
		}
		if(!$openid){
			$this->error('网络繁忙，请稍后重试',U('Agent/order_info',array('id'=>$order_id)),2);	
		}
		//=========步骤2：使用统一支付接口，获取prepay_id============
		//使用统一支付接口
		$unifiedOrder = new UnifiedOrder_pub();
		//设置统一支付接口参数
		$unifiedOrder->setParameter("openid","$openid");//商品描述
		$unifiedOrder->setParameter("body",$order['name']);//商品名称
		//自定义订单号，此处仅作举例
		$timeStamp = time();
		$out_trade_no = WxPayConf_pub::APPID."$timeStamp";
		//$out_trade_no=$order['order_sn'];
		$unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号 
		$unifiedOrder->setParameter("total_fee", $order['total_price']*100);//总金额 单位为分  100分为支付一块钱 $order['total_price']*100
		$unifiedOrder->setParameter("notify_url",'http://m.17cct.com/index.php/Pay/agent_notify_url/');//通知地址 
		$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
		$unifiedOrder->setParameter("attach",$order['id']);
		//非必填参数，商户可根据实际情况选填		
		$prepay_id = $unifiedOrder->getPrepayId();
		//=========步骤3：使用jsapi调起支付============
		$jsApi->setPrepayId($prepay_id);
		$jsApiParameters = $jsApi->getParameters();
		$this->assign('jsApiParameters',$jsApiParameters);
		$this->assign('order_id',$order['id']);
		$this->assign('order_sn',$order['order_sn']);
		$this->display();	
	}

	//全返套餐
	public function agent_notify_url()
	{
		import("@.ORG.WxPay_PHP.WxPayPubHelper");
		import('log_',APP_PATH.'Lib/ORG/WxPay_PHP','.php');
		//$data=$GLOBALS["HTTP_RAW_POST_DATA"];
		//使用通用通知接口
		$notify = new Notify_pub();
		//存储微信的回调
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];	
		$notify->saveData($xml);
		$Common = new Common_util_pub();
		$xml_array_data=$Common->xmlToArray($xml);
		if($xml_array_data['result_code']=='SUCCESS'){//返回数据成功
			session('agent_pay_order_id',null);
			$attach=$xml_array_data['attach'];
		
				//订单信息
				
				$order = M('agent_order')->where(" id=".intval($attach))->find();
				
				if($order['pay_time']==0&&$order['status']==0){

					//更新订单信息
					$data = array('outer_notice_sn'=>$xml_array_data['transaction_id'],'pay_time'=>time(),'pay_status'=>'2');						
					$r = M('agent_order')->where(array('id'=>$order['id']))->save($data);							

					// //发送 商家 短信 微信
					// $storeMsg['user_true_name'] = $u['true_name'];
					// $storeMsg['user_mobile']    = $u['mobile'];
					// $storeMsg['deal_name']   	= $order['project_name'];
					// $storeMsg['deal_tpye'] 		= '3';
					// $storeMsg['store_mobile']   = $store['mobile'];
					//paySuccessSendMsg('store',$storeMsg);	

					
				} 

		}
		//验证签名，并回应微信。
		//对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
		//微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
		//尽可能提高通知的成功率，但微信不保证通知最终能成功。
		if($notify->checkSign() == FALSE){
			$notify->setReturnParameter("return_code","FAIL");//返回状态码
			$notify->setReturnParameter("return_msg","签名失败");//返回信息
		}else{
			$notify->setReturnParameter("return_code","SUCCESS");//设置返回码
		}
		$returnXml = $notify->returnXml();
		//echo $returnXml;
		
		//==商户根据实际情况设置相应的处理流程，此处仅作举例=======
		
		//以log文件形式记录回调信息
		$log_ = new Log_();
		$log_name=APP_PATH."Lib/ORG/WxPay_PHP/notify_url.log";//log文件路径
		$log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");

		if($notify->checkSign() == TRUE)
		{
			if ($notify->data["return_code"] == "FAIL") {
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
			}
			elseif($notify->data["result_code"] == "FAIL"){
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【业务出错】:\n".$xml."\n");
			}
			else{
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【支付成功】:\n".$xml."\n");
			}		
		}	
	}


	//采购订单支付
	public function actionPurchaseGoPay(){
		 	$id = intval($_REQUEST['id']);		 	
		 	if($id){
		 		Yii::$app->session->get('purchase_pay_order_id',$id);
		 		$redirectUrl = urlencode('http://m.17cct.com/index.php/Pay/purchase_order_pay');  
		 		//授权后重定向的回调链接地址，请使用urlencode对链接进行处理 
				$goUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxbd68bd4fe539eba2&redirect_uri=".$redirectUrl."&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect";
		 		$this->redirect($goUrl);
		 	}
	 }

	public function purchase_order_pay()
	{		
		header('Content-type: text/html; charset=utf-8');

		$order_id = intval(session('purchase_pay_order_id'));


		//检查订单（未付款，有效的）		
		//$order = M('agent_order')->field('total_price,name,id,order_sn')->where('pay_status=0  and id='.$order_id)->find();
		$order=M('pms_merge_order')->field('total_price,id,order_sn')->where('id='.$order_id.' and pay_status=0 and system=0 ')->find();
		
		if (!$order||$order['total_price']<=0||!$order_id) {			
			$this->error('非法订单',U('Purchase/index'),3);			
		}	
		import("@.ORG.WxPay_PHP.WxPayPubHelper");
		//使用jsapi接口
		$jsApi = new JsApi_pub();
		//=========步骤1：网页授权获取用户openid============
		//通过code获得openid
		if (!isset($_GET['code']))
		{
			//触发微信返回code码
			$url = $jsApi->createOauthUrlForCode(WxPayConf_pub::JS_API_CALL_URL);
			Header("Location: $url"); 
		}
		else
		{
			//获取code码，以获取openid
		    $code = $_GET['code'];
			$jsApi->setCode($code);
			$openid = $jsApi->getOpenId();
		}
		if(!$openid){
			$this->error('网络繁忙，请稍后重试',U('Purchase/order',array('id'=>$order_id)),2);	
		}
		//=========步骤2：使用统一支付接口，获取prepay_id============
		//使用统一支付接口
		$unifiedOrder = new UnifiedOrder_pub();
		//设置统一支付接口参数
		$unifiedOrder->setParameter("openid","$openid");//商品描述
		$unifiedOrder->setParameter("body",'诚车堂B2B采购平台订单');//商品名称
		//自定义订单号，此处仅作举例
		$timeStamp = time();
		$out_trade_no = WxPayConf_pub::APPID."$timeStamp";
		//$out_trade_no=$order['order_sn'];
		$unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号 
		$unifiedOrder->setParameter("total_fee", $order['total_price']*100);//总金额 单位为分  100分为支付一块钱 $order['total_price']*100
		$unifiedOrder->setParameter("notify_url",'http://m.17cct.com/index.php/Pay/purchase_notify_url/');//通知地址 
		$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
		$unifiedOrder->setParameter("attach",$order['id']);
		//非必填参数，商户可根据实际情况选填		
		$prepay_id = $unifiedOrder->getPrepayId();
		//=========步骤3：使用jsapi调起支付============
		$jsApi->setPrepayId($prepay_id);
		$jsApiParameters = $jsApi->getParameters();
		$this->assign('jsApiParameters',$jsApiParameters);
		$this->assign('order_id',$order['id']);
		$this->assign('order_sn',$order['order_sn']);
		$this->display();	
	}

	//全返套餐
	public function purchase_notify_url()
	{
		import("@.ORG.WxPay_PHP.WxPayPubHelper");
		import('log_',APP_PATH.'Lib/ORG/WxPay_PHP','.php');
		//$data=$GLOBALS["HTTP_RAW_POST_DATA"];
		//使用通用通知接口
		$notify = new Notify_pub();
		//存储微信的回调
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];	
		$notify->saveData($xml);
		$Common = new Common_util_pub();
		$xml_array_data=$Common->xmlToArray($xml);
		if($xml_array_data['result_code']=='SUCCESS'){//返回数据成功
			session('agent_pay_order_id',null);
			$attach=$xml_array_data['attach'];
		
				//订单信息
				
				$order_info = M('pms_merge_order')->where(" id=".intval($attach)." and system=0 ")->find();
				
				if($order_info['pay_time']==0&&$order_info['pay_status']==0){

					//更新订单信息											

					//更新合并订单状态
					$merge_update['outer_notice_sn']=$xml_array_data['transaction_id'];
					$merge_update['pay_time']=time();
					$merge_update['means_of_payment']=2;
					$merge_update['pay_status']=2;
					$r=M('pms_merge_order')->where('id='.$order_info['id'])->save($merge_update);


					//更新子订单状态
					$order_update['outer_notice_sn']=$xml_array_data['transaction_id'];
					$order_update['pay_time']=time();
					$order_update['means_of_payment']=2;
					$order_update['status']=1;
					$order_update['pay_status']=2;
					// $r2 =M('pms_order')->where('id in('.$order_info['order_ids'].')')->save($order_update);
					M('pms_order')->query("update fw_pms_order set outer_notice_sn = '".$xml_array_data['transaction_id']."',pay_time=".time().",status=1,means_of_payment=2,pay_status=2,paid_amount=total_price where id in (".$order_info['order_ids'].")");

					//给门店送优惠券
					$order_list =M('pms_order')->where('system=0 and id in('.$order_info['order_ids'].')')->select();
					
					if($order_list){
						foreach ($order_list as $k => $v) {
							$act_ids[] = $v['act_id'];
						}

						if($act_ids){
							//查询购物送券活动列表中的优惠券id				
							$act_list =M('pms_activity as pa')->join('fw_pms_activity_rule as par on par.act_id=pa.id')->where("pa.act_type=3 and pa.is_del=0 and (unix_timestamp() between pa.start_time and pa.end_time) and pa.id in (".implode(',', $act_ids).")")->select();
							if($act_list){
								foreach ($act_list as $k => $v) {
									$coupon_info = M('pms_coupon')->where('is_del=0 and id='.intval($v['coupon_id']).' and (unix_timestamp() between start_time and end_time) and give_num<total_num')->find();
									if($coupon_info){
										$location_coupon['coupon_name'] = $coupon_info['name'];
										$location_coupon['full_money'] = $coupon_info['full_money'];
										$location_coupon['discount_money'] = $coupon_info['discount_money'];
										$location_coupon['num'] = 1;
										$location_coupon['give_num'] = 1;
										$location_coupon['coupon_id'] = $coupon_info['id'];
										$location_coupon['goods_ids'] = $coupon_info['goods_ids'];
										$location_coupon['start_time'] = $coupon_info['start_time'];
										$location_coupon['end_time'] = $coupon_info['end_time'];
										$location_coupon['supplier_id'] = $coupon_info['supplier_id'];
										$location_coupon['add_time'] = time();
										$location_coupon['type'] = 1;
										$location_coupon['location_id'] = intval($order_info['location_id']);
										M('pms_location_coupon')->add($location_coupon);
										$coupon_ids[] = $coupon_info['id'];
									}
								}

								if($coupon_ids){
									//更新优惠券赠送数量
									M('pms_coupon')->where('id in('.implode(',',$coupon_ids).')')->setInc('give_num');							
								}
							}
						}
					}					
				} 

		}
		//验证签名，并回应微信。
		//对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
		//微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
		//尽可能提高通知的成功率，但微信不保证通知最终能成功。
		if($notify->checkSign() == FALSE){
			$notify->setReturnParameter("return_code","FAIL");//返回状态码
			$notify->setReturnParameter("return_msg","签名失败");//返回信息
		}else{
			$notify->setReturnParameter("return_code","SUCCESS");//设置返回码
		}
		$returnXml = $notify->returnXml();
		//echo $returnXml;
		
		//==商户根据实际情况设置相应的处理流程，此处仅作举例=======
		
		//以log文件形式记录回调信息
		$log_ = new Log_();
		$log_name=APP_PATH."Lib/ORG/WxPay_PHP/notify_url.log";//log文件路径
		$log_->log_result($log_name,"【接收到的notify通知】:\n".$xml."\n");

		if($notify->checkSign() == TRUE)
		{
			if ($notify->data["return_code"] == "FAIL") {
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
			}
			elseif($notify->data["result_code"] == "FAIL"){
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【业务出错】:\n".$xml."\n");
			}
			else{
				//此处应该更新一下订单状态，商户自行增删操作
				$log_->log_result($log_name,"【支付成功】:\n".$xml."\n");
			}		
		}	
	}



    public function chequan_order_pay()
    {
        header('Content-type: text/html; charset=utf-8');

        $order_id = intval(session('chequan_pay_order_id'));


        //检查订单（未付款，有效的）
        $order = M('chequan_payment')->field('*')->where('pay_status=0  and id='.$order_id)->find();
        //and open_id="'.session('oprate_opend_id').'"

        if (!$order||$order['pay_money']<=0||!$order_id) {
            $this->error('非法订单',U('index'),3);
        }
        import("@.ORG.WxPay_PHP.WxPayPubHelper");
        //使用jsapi接口
        $jsApi = new JsApi_pub();
        //=========步骤1：网页授权获取用户openid============
        //通过code获得openid
        if (!isset($_GET['code']))
        {
            //触发微信返回code码
            $url = $jsApi->createOauthUrlForCode(WxPayConf_pub::JS_API_CALL_URL);
            Header("Location: $url");
        }
        else
        {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $jsApi->setCode($code);
            $openid = $jsApi->getOpenId();
        }
        if(!$openid){
            $this->error('网络繁忙，请稍后重试',U('index'),2);
        }
        //=========步骤2：使用统一支付接口，获取prepay_id============
        //使用统一支付接口
        $unifiedOrder = new UnifiedOrder_pub();
        //设置统一支付接口参数
        $unifiedOrder->setParameter("openid","$openid");//商品描述
        $unifiedOrder->setParameter("body",'车圈预付款-$order["order_sn"]');//商品名称
        //自定义订单号，此处仅作举例
        $timeStamp = time();
        $out_trade_no = WxPayConf_pub::APPID."$timeStamp";
        //$out_trade_no=$order['order_sn'];
        $unifiedOrder->setParameter("out_trade_no","$out_trade_no");//商户订单号
        $unifiedOrder->setParameter("total_fee", $order['pay_money']*100);//总金额 单位为分  100分为支付一块钱 $order['total_price']*100
        $unifiedOrder->setParameter("notify_url",'http://m.17cct.com/index.php/Chequan/chequan_notify_url/');//通知地址
        // $unifiedOrder->setParameter("notify_url",'http://192.168.2.15/mobile/index.php/Chequan/agent_notify_url/');//通知地址
        $unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
        $unifiedOrder->setParameter("attach",$order['id']);
        //非必填参数，商户可根据实际情况选填
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);
        $jsApiParameters = $jsApi->getParameters();
        $this->assign('jsApiParameters',$jsApiParameters);
        $this->assign('order_id',$order['id']);
        // $this->assign('order_sn',$order['order_sn']);
        $this->display();
    }

}
?>