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

require_once 'jiami.php';

class BizController extends Controller {

	//去掉默认布局
    public $layout = false;
    // public $default = 'index';
	//禁用表单csrf
    public $enableCsrfValidation = false;
    // public $layout = 'header.php';

    public function actionIndex()
	{	
		$session = Yii::$app->session;
		// $account_info = $session->get('account_info');
		// $language = $session['language'];
		//设置
		// $session->set('language', 'en-US');
		if(!$session['account_info']){
				//$url = Url::toRoute(['product/view', 'id' => 42]);
				$url = Url::toRoute('biz/login');
				header("Location:".$url);
		}else{
			$account_info = $session['account_info'];
			if($account_info['type']=='1'||$account_info['type']=='2'){
			// var_dump($account_info);die;
				$url = Url::toRoute(['entrance','id'=>$account_info['location_ids'][0]]);
			// var_dump($url);die;
				header("Location:".$url);
			}else{
				// $this->display();
				return $this->render('index');
			}
			
		}	


	}
	 public function actionVerify()
	{	
		if(!session('account_info')){
				header("Location:".U("Biz/login"));
			}else{
				$this->display();
		}	
	}
	
	public function actionajax_verify(){
		$account_info=session('account_info');
		if(!$account_info['id']){
			$this->ajaxReturn(U('Biz/login'),'请重新登录',0);
		}		
		$now = time();
		$sn = trim($_REQUEST['dhm']);
		session('sn','');	
		$supplier_id = intval($account_info['supplier_id']);
		$coupon_data=M('deal_coupon')->join('fw_deal_order as fdo on fdo.id=fw_deal_coupon.order_id')->join('fw_deal_order_item as doi on fw_deal_coupon.order_deal_id=doi.id')->field('fw_deal_coupon.id,fw_deal_coupon.deal_id,doi.name,doi.attr,doi.number,doi.unit_price,fw_deal_coupon.sn,fw_deal_coupon.supplier_id,fw_deal_coupon.order_id,fw_deal_coupon.confirm_time,fdo.order_sn,fdo.create_time,fdo.total_price')->where("sn='".$sn."' and supplier_id in(".$supplier_id.") and is_valid=1 and fw_deal_coupon.is_delete=0 ")->find();//and begin_time<".$now." and (end_time=0 or end_time>".$now.")
		
		if($coupon_data)
			{
				
				$deal_info=M('deal')->where('id='.$coupon_data['deal_id']." and location_id in(".implode(",",$account_info['location_ids']).")")->find();
				
				if(!$deal_info)
				{
					$result['status'] = 0;
					$result['msg'] = '没有门店权限管理该数据';
					$this->ajaxReturn($result);
				}

				if($coupon_data['supplier_id']!=$supplier_id)
				{
					$result['status'] = 0;
					$result['msg'] = '该券为其他团购商户的团购券，不能确认';
					$this->ajaxReturn($result);
				}
				elseif($coupon_data['confirm_time'] > 0)
				{
					$result['status'] = 0;
					$result['msg'] = '该服务券已于'.date('Y-m-d H:i:s',$coupon_data['confirm_time'])."使用";
					$this->ajaxReturn($result);
				}
				else
				{
					if($coupon_data['attr']){
						$sql = "select dar.attr_value,gta.name from fw_deal_attr_record as dar left join fw_goods_type_attr as gta on gta.id=dar.goods_type_attr_id  where  dar.id in (".$coupon_data['attr'].")";
						$attr_info = M()->query($sql);
						foreach ($attr_info as $k => $v) {
							$attrs.=$v['name'].':'.$v['attr_value'].';  ';
						}
						if($attrs){
							$attrs='('.$attrs.')';
						}
					}
					$deal_type=intval(M('deal')->where('id='.$coupon_data['deal_id'])->field('deal_type')->find());
					
					$order_info=M('deal_order')->where('id='.intval($coupon_data['order_id']))->field('type,pay_status,total_price')->find();
					$referer=explode('_',$order_info['referer']);
					if($referer[0]=='PC-CARD'&&$order_info['memo']!=''||$referer[0]=='WX-CARD'&&$order_info['memo']!=''){
						$number_info=',车牌号码为:'.$order_info['memo']."。";
					}
					if(!$order_info['type']&&$order_info['pay_status']==2){
						$order_msg=",已在线支付￥".price($coupon_data['unit_price']).",请确认已服务".$number_info;
					}else{
						$order_msg=",到店付款,请确认已付款￥".price($coupon_data['unit_price']);
					}


					$result['status'] = 1;	
					session('sn',$sn);	
					$this->assign('cd',$coupon_data);
					$html=$this->fetch('Biz/verify_info');
					$result['msg'] = $html;	
					$this->ajaxReturn($result);

					$result['msg'] = "该服务码所预订的服务为[".$deal_info['name']."/".$deal_info['sub_name']."]".$attrs.$order_msg;				
					/*if($deal_type == 1)
					{
						$result['msg'] = "该服务码所预订的服务为[".$deal_info['name']."/".$deal_info['sub_name']."]".$order_msg;
					}
					else
					{
						$result['msg'] = "该服务码所预订的服务为[".$deal_info['name']."/".$deal_info['sub_name']."]".$order_msg;
					}
					$this->ajaxReturn($result);*/
				}
			}
			else
			{		
					$result['status'] = 0;
					$result['msg'] = '无效的验证码';	
					$this->ajaxReturn($result);
			}
		
	}
	public function actionuser_account(){
		$account_info=session('account_info');
		if(!$account_info['id']){
			$this->ajaxReturn(U('Biz/login'),'请重新登录',0);
		}	
		$now = time();
		$sn = trim(session('sn'));		
		$supplier_id = intval($account_info['supplier_id']);
		$coupon_data=M('deal_coupon')->join('fw_deal_order_item as doi on fw_deal_coupon.order_deal_id=doi.id')->field('fw_deal_coupon.id,fw_deal_coupon.deal_id,doi.name,doi.number,fw_deal_coupon.sn,fw_deal_coupon.supplier_id,fw_deal_coupon.confirm_time,fw_deal_coupon.order_id')->where("sn='".$sn."' and supplier_id in(".$supplier_id.") and is_valid=1 and is_delete=0 ")->find();//and begin_time<".$now." and (end_time=0 or end_time>".$now.")
		if($coupon_data)
			{
				$deal_info=M('deal')->where('id='.intval($coupon_data['deal_id'])." and location_id in(".$account_info['location_ids'].")");
				if(!$deal_info)
				{
					$result['status'] = 0;
					$result['msg'] = '没有门店权限管理该数据';

					$this->ajaxReturn($result);
				}

				if($coupon_data['supplier_id']!=$supplier_id)
				{
					$result['status'] = 0;
					$result['msg'] = '该券为其他团购商户的团购券，不能确认';
				
					$this->ajaxReturn($result);
				}
				elseif($coupon_data['confirm_time'] > 0)
				{
					$result['status'] = 0;
					$result['msg'] = '该服务券已于'.date('Y-m-d H:i:s',$coupon_data['confirm_time'])."使用";
					
					$this->ajaxReturn($result);
				}
				else
				{				
					$data['confirm_account']=$account_info['id'];
					$data['confirm_time']=$now;
					$r=M('deal_coupon')->where(array('id'=>intval($coupon_data['id'])))->save($data);
					$result['status'] = 1;
					$data_type=intval(M('deal')->where(array('id'=>intval($coupon_data['deal_id'])))->field('deal_type')->find());			
					/*if($deal_type == 1)
					{
						$result['msg'] = $coupon_data['name']."(购买数量：".$coupon_data['number'].")"."确认成功,确认时间为:".date('Y-m-d H:i:s',$now);
					}
					else
					{
						
					}*/
					$result['msg'] = $coupon_data['name']."确认成功,确认时间为:".date('Y-m-d H:i:s',$now);

					$rs = order_paid($coupon_data['order_id']);  
					/*require_once APP_ROOT_PATH."system/libs/cart.php";
					$rs = order_paid($coupon_data['order_id']);  
					send_use_coupon_sms(intval($coupon_data['id'])); //发送团购券确认消息
					send_use_coupon_mail(intval($coupon_data['id'])); //发送团购券确认消息*/
					$this->ajaxReturn($result);
				}
			}
			else
			{		
				$this->ajaxReturn(0,'无效的验证码',0);
			}
	}
	
	public function actionLogin(){
		// $url = Url::to("biz/index");
		// var_dump($url);die;
		$session = Yii::$app->session;
		$cookies = Yii::$app->request->cookies;
		if(!$session['account_info']){
			// var_dump($session);die;
			return $this->render('login',[
				'title'        => '商家登录',
				'account_name' => $cookies->get("account_name"),
				"account_pwd"  => passport_decrypt($cookies->get("account_password"),'17cct_com_biz_login_key')
			]);
		}else{
			$url = Url::toRoute("index");
			header("Location:".$url);
		}	
		
	}

	public function actionAjaxLogin(){
		$account_name = Yii::$app->request->post('username');
		$account_password = Yii::$app->request->post('password');
		if(!$account_name){
			return json_encode(array('status'=>0,'info'=>'用户名不能为空!','data'=>0));
		}
		if(!$account_password){
			return json_encode(array('status'=>0,'info'=>'密码不能为空!','data'=>0));
		}

		$account = SupplierAccount::find()->where(['account_name' => $account_name, 'is_effect' => 1, 'is_delete'=>0])->one();
		if($account->account_password == md5($account_password)){
			$sql        = "select * from fw_supplier_account_location_link where account_id='".intval($account->id)."'";
			$account_locations = Yii::$app->db->createCommand($sql)->queryAll();
			$account_location_ids = array();
			foreach($account_locations as $row)
			{
				$account_location_ids[] = $row['location_id'];
			}
			if(intval($_REQUEST['remember'])==1)
			{
				$cookie = Yii::$app->request->cookie;
				// 在要发送的响应中添加一个新的 cookie
				$cookies->add(new \yii\web\Cookie([
						'name'   => 'account_name',
						'value'  => $account_name,
						'expire' => time()+3600*24*30
				]));
				$key = "17cct_com_biz_login_key";
				$cookies->add(new \yii\web\Cookie([
						'name'   => 'account_password',
						'value'  => passport_encrypt($account_password,$key),
						'expire' => time()+3600*24*30
				]));
			}
			$account = $account->attributes; 
			$account['location_ids'] = $account_location_ids;
			Yii::$app->session->set('account_info',$account);
			$d['login_time'] = time();
			$d['login_ip']   = $_SERVER['REMOTE_ADDR'];
			// $r=M("supplier_account")->where(array('id'=>intval($account['id'])))->save($d);
			// $r = SupplierAccount::find()->where(['id'=>intval($account->id)])->update($d);
			// $customer = Customer::findOne($id); $customer->email = 'james@example.com'; $customer->save();
			$SupplierAccount = SupplierAccount::findOne(intval($account['id']));
			$SupplierAccount->login_time = time();
			$SupplierAccount->login_time = time();
			$SupplierAccount->login_ip 	 = $_SERVER['REMOTE_ADDR'];
			$r = $SupplierAccount->save();
			// $r = SupplierAccount::find()->updateByPk(intval($account->id),$d);
			// $connection->createCommand()->update('fw_supplier_account', ['status' => 1], $d)->execute();
			if($account['type'] == '1'){
				$redirect_url = Url::toRoute(['entrance','id'=>$account['location_ids'][0]]);
				if(Yii::$app->session->get('redirect_url')){
					$redirect_url = Yii::$app->session->get('redirect_url');
				}
				return json_encode(array('status'=>1,'info'=>$redirect_url,'data'=>0));
			}
			return json_encode(array('status'=>1,'info'=>Url::toRoute('biz/index'),'data'=>0));

		}else{
			return json_encode(array('status'=>0,'info'=>'用户名或密码错误!','data'=>0));
		}		

	}

	public function actionLoginOut(){
		Yii::$app->session->set('account_info',null);
		$this->redirect(Url::toRoute("login"));
	}

	public function actionorder(){		
		if(!session('account_info')){
			header("Location:".U("Biz/index"));
			exit;
		}
		$account_info=session('account_info');
			
		$d_w['fw_deal.supplier_id']=$account_info['supplier_id'];
		$d_w['do.is_delete']=0;
		$d_w['do.after_sale']=0;
		$d_w['do.referer']!='刷单';
		$status=intval($_REQUEST['status']);
		if(isset($status)&&$status!=''){
			$result=array();
			//未消费的订单
			if($status=='1'){
				$arr_status=array('1','2');
			//已消费的订单
			}elseif ($status=='-1') {
				$arr_status=array('3');
			}
			$order = M("deal")->join("fw_deal_order_item as dot on dot.deal_id=fw_deal.id")->join("fw_deal_order as do on dot.order_id=do.id")->join("fw_deal_coupon as fdc on fdc.order_id=do.id")->field('do.id,do.user_name,do.mobile,do.order_sn,do.create_time,do.total_price,do.pay_status,do.type,do.order_status,do.is_delete,dot.name,dot.sub_name,MIN(fdc.confirm_time) AS `min_confirm`,MAX(fdc.confirm_time) AS `max_confirm`,MAX(fdc.end_time) AS `max_end`')->where($d_w)->group('fdc.id')->select();	
			
			foreach ($order as $k => $v) {
				$order[$k]['status']=get_order_status($v);
				$order[$k]['total_price']=price($order[$k]['total_price']);

				if(in_array($order[$k]['status'], $arr_status)){	
				    array_push($result,$order[$k]);
				}
			}
			$order_count=count($result);
		}else{
			//全部订单
			$order_count = M("deal")->join("fw_deal_order_item as dot on dot.deal_id=fw_deal.id")->join("fw_deal_order as do on dot.order_id=do.id")->where($d_w)->count();	
		}
		if(!$order_count||$order_count<8){
			$order_count=1;
		}else{		
			$order_count=round(($order_count/8));
		}

        $this->assign("order_count",$order_count);
       	$this->assign("status",$status);
		$this->display();
	}
	public function actionajax_get_order()
	{
		$account_info=session('account_info');
		$d_field='do.id,do.user_name,do.mobile,do.order_sn,do.create_time,do.total_price,do.pay_status,do.type,do.order_status,do.is_delete,dot.order_id,dot.name,dot.sub_name,fw_deal.is_coupon,MIN(fdc.confirm_time) AS `min_confirm`,MAX(fdc.confirm_time) AS `max_confirm`,MAX(fdc.end_time) AS `max_end`';
		$d_w['fw_deal.supplier_id']=$account_info['supplier_id'];                                                                                     
		$d_w['do.is_delete']=0;
		$d_w['do.after_sale']=0;		
		$d_w['do.referer']=array('neq','刷单');
		$d_w['do.type']=array('neq','6');
		$page=intval($_GET['p']);
		if(isset($_REQUEST['status'])&&$_REQUEST['status']!=0){
			$result=array();
			//未消费的订单
			if($_REQUEST['status']=='1'){
				$arr_status=array('1','2');
			//已消费的订单
			}elseif ($_REQUEST['status']=='-1') {
				$arr_status=array('3');
			}
			$order_list = M("deal")->join("fw_deal_order_item as dot on dot.deal_id=fw_deal.id")->join("fw_deal_order as do on dot.order_id=do.id")->join("fw_deal_coupon as fdc on fdc.order_id=do.id")->field($d_field)->where($d_w)->order('do.create_time desc')->group('fdc.id')->select();	

			foreach ($order_list as $k => $v) {
				$order_list[$k]['status']=get_order_status($v);
				$order_list[$k]['total_price']=price($order_list[$k]['total_price']);
				if(in_array($order_list[$k]['status'], $arr_status)){
				    array_push($result,$order_list[$k]);
				}
			}
			$order=array_slice($result, $page*8,8);
		}else{
			if($page<0) die();
			$limit=($page*8).",8";
			//全部订单
			$order = M("deal")->join("fw_deal_order_item as dot on dot.deal_id=fw_deal.id")->join("fw_deal_order as do on dot.order_id=do.id")->join("fw_deal_coupon as fdc on fdc.order_id=do.id")->field($d_field)->where($d_w)->order('do.create_time desc')->limit($limit)->group('fdc.id')->select();	
			
			foreach ($order as $k => $v) {
				$order[$k]['status']=get_order_status($v);
				$order[$k]['total_price']=price($order[$k]['total_price']);
			}			
		}
		$this->assign('list',$order);
		echo $html=$this->fetch();
	}

	 public function actionfast_order()
	{
		if(!session('account_info')){
			header("Location:".U("Biz/index"));
			exit;
		}
		$account_info=session('account_info');

		$s_locations = M("supplier_location")->field('id,name,address,tel,mobile')->where(array('supplier_id'=>intval($account_info['supplier_id'])))->select();
		$s_locations_ids=array();
		foreach ($s_locations as $k => $v) {
			array_push($s_locations_ids, $v['id']);
		}
		$fast_order_count = M("msg")->where('sid in  ('.implode(",",$s_locations_ids).')')->order('addtime desc')->count();

		if(!$fast_order_count||$fast_order_count<8){
			$fast_order_count=1;
		}else{		
			$fast_order_count=round(($fast_order_count/8));
		}
        $this->assign("fast_order_count",$fast_order_count);
		$this->display();
	}

	public function actionajax_get_fast_order()
	{
		$account_info=session('account_info');

		$page=intval($_GET['p'])-1;
		if($page<0) die();
		$limit=($page*8).",8";

		$s_locations = M("supplier_location")->field('id,name,address,tel,mobile')->where(array('supplier_id'=>intval($account_info['supplier_id'])))->select();
		$s_locations_ids=array();
		foreach ($s_locations as $k => $v) {
			array_push($s_locations_ids, $v['id']);
		}
		$result = M("msg")->where('sid in  ('.implode(",",$s_locations_ids).')')->order('addtime desc')->limit($limit)->select();
		$this->ajaxReturn(0,$result,0);
	}

	//商家会员管理
	public function actionmember()
	{
		if(!session('account_info')){
			header("Location:".U("Biz/index"));
			exit;
		}
		$this->display();
	}

	//商家会员添加
	public function actionadd_member()
	{
		
		$this->get_member_parameter();
		$this->display();
	}

	//写入商家会员信息
	public function actioninsert_member(){

		$info=session('account_info');		
		if(!$info){
			$arr['info']='请先登录再操作';
			$arr['status']=0;
			$arr['url']=U('Biz/login');
			$this->ajaxReturn($arr);
		}
		$data['mobile']=trim($_REQUEST['mobile']);
		if(!isMobile($data['mobile'])){
			$arr['info']='号码格式不正确';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}
		$location_ids = array_values(array_filter($info['location_ids']));
		$data['supplier_id']=$info['supplier_id'];
		$data['location_id']=$location_ids[0];
	
		$data['user_id']=intval($_REQUEST['user_id']);
		
		$data['user_name']=trim($_REQUEST['user_name']);
		$data['birthday']=strtotime($_REQUEST['birthday']);
		if($_REQUEST['car_sn']){			
			$data['car_sn']=trim($_REQUEST['car_sn_prev']).strtoupper(trim($_REQUEST['car_sn']));
		}
		$data['buy_time']=trim($_REQUEST['buy_time']);
		$data['brand_id']=trim($_REQUEST['brand_id']);
		$data['factory_id']=trim($_REQUEST['factory_id']);
		$data['models_id']=trim($_REQUEST['models_id']);
		$data['car_frame_no']=strtoupper(trim($_REQUEST['car_frame_no']));
		$data['car_engine_no']=strtoupper(trim($_REQUEST['car_engine_no']));
		$data['new_mileage']=trim($_REQUEST['new_mileage']);
		$data['next_insurance_time']=strtotime($_REQUEST['next_insurance_time']);
		$data['next_maintain_time']=strtotime($_REQUEST['next_maintain_time']);
		$data['next_annually_time']=strtotime($_REQUEST['next_annually_time']);
		$data['next_time_mileage']=trim($_REQUEST['next_time_mileage']);

		$data['displacement']=trim($_REQUEST['displacement']);
		$data['chassis_sn']=strtoupper(trim($_REQUEST['chassis_sn']));
		$data['car_color']=trim($_REQUEST['car_color']);
		$data['car_price']=trim($_REQUEST['car_price']);
		$data['next_visit_time']=strtotime($_REQUEST['next_visit_time']);

		$data['tire_brand_id']=intval($_REQUEST['tire_brand_id']);
		$data['tread']=trim($_REQUEST['tread']);
		$data['flat_ratio']=trim($_REQUEST['flat_ratio']);
		$data['diameter']=trim($_REQUEST['diameter']);
		$data['add_time']=time();
		$data['speed_level']=trim($_REQUEST['speed_level']);
		$data['insurance_company']=trim($_REQUEST['insurance_company']);
		$data['remarks']=trim($_REQUEST['remarks']);
		$r=M('shop_user_info')->add($data);
		if($r){
			$arr['info']='录入成功';
			$arr['status']=1;
			$arr['url']=U('Biz/member');
			$this->ajaxReturn($arr);
		}else{
			$arr['info']='录入失败';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}

	}

	public function actionget_member_parameter(){
		//车牌信息
		$prov=array('粤','浙','京','沪','川','津','渝','鄂','赣','冀','蒙','鲁','苏','辽','吉','皖','湘','黑','琼','贵','桂','云','藏','陕','甘','宁','青','豫','闽','新','晋');

		$letter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');

		$py = new CUtf8_PY();
		foreach ($prov as $k => $v) {
			foreach ($letter as $k1 => $v1) {
				$prov_letter[ucwords($py->encode($v))][$v][]=$v.$v1;
			}			
		}
		ksort($prov_letter);
		//排量
		$displacement=array('1'=>'1.0L以下','2'=>'1.0L','3'=>'1.1L','4'=>'1.3L','5'=>'1.4L','6'=>'1.5L','7'=>'1.6L','8'=>'1.7L','9'=>'1.8L');
		//颜色
		$color=array('卡其'=>'卡其','粉'=>'粉','银'=>'银','灰'=>'灰','黑'=>'黑','蓝'=>'蓝','白'=>'白','黄'=>'黄','红'=>'红');
		//车价范围
		$price=array('1'=>'5万以下','2'=>'5万到10万','3'=>'10万到20万','4'=>'20万到30万','5'=>'30万到50万','6'=>'50万到80万','7'=>'80万到100万','8'=>'100万以上');
		//胎面
		$tread=array('700'=>'700','650'=>'650','55'=>'55','37X'=>'37X','35'=>'35','32*11.5'=>'32*11.5','32X'=>'32X','325'=>'325','31*10.5'=>'31*10.5','31X'=>'31X','315'=>'315','31'=>'31','30*9.5'=>'30*9.5','30X'=>'30X','305'=>'305','30'=>'30','295'=>'295','285'=>'285','275'=>'275','265'=>'265','255'=>'255','245'=>'245','235'=>'235','225'=>'225','215'=>'215','210'=>'210','205'=>'205','196'=>'196','195'=>'195','185'=>'185','175'=>'175','165'=>'165','155'=>'155','145'=>'145','135'=>'135');
		//扁平比
		$flat_ratio=array('790'=>'790','105'=>'105','85'=>'85','80'=>'80','75'=>'75','70'=>'70','65'=>'65','60'=>'60','55Z'=>'55Z','55'=>'55','50Z'=>'50Z','50'=>'50','45'=>'45','40'=>'40','35'=>'35','30'=>'30','25'=>'25','16'=>'16','12.5'=>'12.5','11.5'=>'11.5','10.5'=>'10.5','9.5'=>'9.5');
		//直径
		$diameter=array('540'=>'540','45'=>'45','28'=>'28','26'=>'26','24'=>'24','23'=>'23','22'=>'22','21'=>'21','20'=>'20','19'=>'19','18'=>'18','17'=>'17','16C'=>'16C','16.5'=>'16.5','16'=>'16','15C'=>'15C','15'=>'15','14C'=>'14C','14'=>'14','13C'=>'13C','13'=>'13','12C'=>'12C','11'=>'11');
		//速度级别
		$speed_level=array('ZR'=>'ZR','Z'=>'Z','Y'=>'Y','W'=>'W','V/H'=>'V/H','T/H'=>'T/H','T'=>'T','S/T'=>'S/T','S'=>'S','Q'=>'Q','R'=>'R','P'=>'P','N'=>'N','L'=>'L','H'=>'H','G'=>'G','91'=>'91','85'=>'85');
		//保险公司
		$insurance_company=array('1'=>'安盛天平','2'=>'阳光车险','3'=>'平安车险','4'=>'人保车险','5'=>'太平洋车险','6'=>'大地车险','7'=>'中国太保','8'=>'国寿财险','9'=>'天安保险','10'=>'永安保险','11'=>'安邦保险','12'=>'长安保险','13'=>'民安保险','14'=>'太平保险','15'=>'国泰保险','16'=>'永诚保险','17'=>'都邦保险','18'=>'华农保险','19'=>'天平保险','20'=>'中华保险','21'=>'华泰保险','22'=>'安诚保险','23'=>'浙商保险','24'=>'紫金保险','25'=>'渤海保险','26'=>'信达保险','27'=>'泰山保险','28'=>'中银保险','29'=>'安信农业','30'=>'英大泰和','31'=>'华安保险','32'=>'大众保险','33'=>'安心保险','34'=>'人寿保险','35'=>'人寿财险','36'=>'中华联合保险','37'=>'利宝财险','38'=>'众城保险','39'=>'鼎和保险');
		
		for ($i=0; $i<=8; $i++)
		{
		    $years[$i] = (date('Y') - $i).'年';
		}	

		//品牌
		$car_brand_list =M('car')->field('id,name')->where('parent_id=0')->select(); 
		foreach($car_brand_list as $k => $v){
			$fc = strtoupper(substr($py->encode($v['name'], 1), 0, 1));
			$brand_list[$fc][$v['name']] = M('car')->field('parent_id,id,name')->where('parent_id='.intval($v['id'])." and level=1")->select();
			//$car_brand_list[$k]['name'] = $fc."　".$v['name'];
		}	
		//var_dump($brand_list);
		//array_multisort($car_brand_list_key, $car_brand_list);



		//轮胎品牌
		$tire_brand=M('tire_brand')->where('is_effect=1')->select();
		$this->assign('tire_brand', $tire_brand);
		$this->assign('years',$years);	
		$this->assign('displacement',$displacement);	
		$this->assign('color',$color);	
		$this->assign('price',$price);
			
		$this->assign('tread',$tread);
		$this->assign('flat_ratio',$flat_ratio);	
		$this->assign('diameter',$diameter);
		$this->assign('speed_level',$speed_level);
		$this->assign('brand_list',$brand_list);
		//$this->assign('letter',$letter);
		$this->assign('prov_letter',$prov_letter);
		$this->assign('insurance_company',$insurance_company);
	}

	//录入会员资料查找会员
	public function actionget_member_info(){
		$mobile=trim($_REQUEST['mobile']);
		if(!isMobile($mobile)){
			$arr['info']='手机号码格式不正确';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}
		$info=M('user')->field('id,true_name')->where('mobile='.$mobile." and is_effect=1")->find();
		if($info){
			$arr['info']='手机号码格式不正确';
			$arr['status']=1;
			$arr['user_name']=$info['user_name'];
			$arr['user_id']=$info['id'];
			$this->ajaxReturn($arr);
		}else{
			$arr['info']='该号码未注册,请先注册成为会员';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}
	}

	//获取车型
	public function actionajax_get_models(){
		$id=intval($_REQUEST['id']);
		if(!$id){
			echo '';
			exit;
		}
		$py= new CUtf8_PY();
		$car_models_list =M('car')->field('id,name')->where('parent_id='.$id." and level=2")->select(); 
		foreach ($car_models_list as $k => $v) {
			$fc = strtoupper(substr($py->encode($v['name'], 1), 0, 1));
			$cml[$fc][]=$v;
		}
		$this->assign('car_models_list',$cml);
		echo $html=$this->fetch();
	}

	public function actionajax_get_member(){
		$account_info=session('account_info');

		$page=intval($_GET['p']);
		if($page<0) die();
		$limit=($page*8).",8";

		$result = M("shop_user_info")->field('id,user_name,mobile,add_time')->where(array('supplier_id'=>intval($account_info['supplier_id']),'is_delete'=>0))->limit($limit)->select();
		$this->assign('result',$result);
		echo $html=$this->fetch();
	}
	//删除门店会员
	public function actiondel_member(){
		$id=intval($_REQUEST['id']);
		if(!$id){
			$arr['info']='非法操作';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}
		$account_info=session('account_info');

		$info=M('shop_user_info')->where('id='.$id." and supplier_id=".intval($account_info['supplier_id']))->find();
	
		if(!$info){
			$arr['info']='无权限操作';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}
		$data['is_delete']=1;
		$r=M('shop_user_info')->where('id='.$id)->save($data);
		if($r){
			$arr['info']='删除成功';
			$arr['status']=1;
			$this->ajaxReturn($arr);
		}else{
			$arr['info']='删除失败,请重试';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}
	}

	//编辑会员
	public function actionedit_member(){
		$id=intval($_REQUEST['id']);
		if(!$id){
			$this->error('非法操作',U('Biz/member'),3);
		}
		
		$account_info=session('account_info');

		$member=M('shop_user_info')->where('id='.$id." and supplier_id=".intval($account_info['supplier_id']))->find();
	
		if(!$member){
			$this->error('无权限操作',U('Biz/member'),3);
		}
		$member['birthday']=$member['birthday']==0?'':date('Y-m-d',$member['birthday']);
		$member['next_insurance_time']=$member['next_insurance_time']==0?'':date('Y-m-d',$member['next_insurance_time']);
		$member['next_maintain_time']=$member['next_maintain_time']==0?'':date('Y-m-d',$member['next_maintain_time']);
		$member['next_annually_time']=$member['next_annually_time']==0?'':date('Y-m-d',$member['next_annually_time']);
		$member['next_visit_time']=$member['next_visit_time']==0?'':date('Y-m-d',$member['next_visit_time']);
		if($member['car_sn']){
			$member['car_sn_1']=mb_substr($member['car_sn'],0,2,'utf-8');
	    	$member['car_sn_2']=mb_substr($member['car_sn'],2,5,'utf-8'); 
		}
		if($member['factory_id'])
		$member['factory_name']=M('car')->where('id='.$member['factory_id'])->getField('name');

		if($member['models_id'])
		$member['models_name']=M('car')->where('id='.$member['models_id'])->getField('name');
		$this->assign('m',$member);
		$this->get_member_parameter();
		$this->display();
	}

	//修改商家会员信息
	public function actionupdate_member(){
		$id=intval($_REQUEST['id']);
		if(!$id){
			$arr['info']='非法操作';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}
		$account_info=session('account_info');
		if(!$account_info){
			$arr['info']='请先登录再操作';
			$arr['status']=0;
			$arr['url']=U('Biz/login');
			$this->ajaxReturn($arr);
		}
		$member=M('shop_user_info')->field('id')->where('id='.$id." and supplier_id=".intval($account_info['supplier_id']))->find();	
		if(!$member){
			$arr['info']='无权限操作';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}
		
		$data['mobile']=trim($_REQUEST['mobile']);
		if(!isMobile($data['mobile'])){
			$arr['info']='号码格式不正确';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}
		$data['user_name']=trim($_REQUEST['user_name']);
		$data['birthday']=strtotime($_REQUEST['birthday']);
		if($_REQUEST['car_sn']){			
			$data['car_sn']=trim($_REQUEST['car_sn_prev']).strtoupper(trim($_REQUEST['car_sn']));
		}
		$data['buy_time']=trim($_REQUEST['buy_time']);
		$data['brand_id']=trim($_REQUEST['brand_id']);
		$data['factory_id']=trim($_REQUEST['factory_id']);
		$data['models_id']=trim($_REQUEST['models_id']);
		$data['car_frame_no']=strtoupper(trim($_REQUEST['car_frame_no']));
		$data['car_engine_no']=strtoupper(trim($_REQUEST['car_engine_no']));
		$data['new_mileage']=trim($_REQUEST['new_mileage']);
		$data['next_insurance_time']=strtotime($_REQUEST['next_insurance_time']);
		$data['next_maintain_time']=strtotime($_REQUEST['next_maintain_time']);
		$data['next_annually_time']=strtotime($_REQUEST['next_annually_time']);
		$data['next_time_mileage']=trim($_REQUEST['next_time_mileage']);

		$data['displacement']=trim($_REQUEST['displacement']);
		$data['chassis_sn']=strtoupper(trim($_REQUEST['chassis_sn']));
		$data['car_color']=trim($_REQUEST['car_color']);
		$data['car_price']=trim($_REQUEST['car_price']);
		$data['next_visit_time']=strtotime($_REQUEST['next_visit_time']);

		$data['tire_brand_id']=intval($_REQUEST['tire_brand_id']);
		$data['tread']=trim($_REQUEST['tread']);
		$data['flat_ratio']=trim($_REQUEST['flat_ratio']);
		$data['diameter']=trim($_REQUEST['diameter']);
		$data['add_time']=time();
		$data['speed_level']=trim($_REQUEST['speed_level']);
		$data['insurance_company']=trim($_REQUEST['insurance_company']);
		$data['remarks']=trim($_REQUEST['remarks']);
		$r=M('shop_user_info')->where('id='.intval($_REQUEST['id']))->save($data);
		if($r){
			$arr['info']='修改成功';
			$arr['status']=1;
			$arr['url']=U('Biz/member');
			$this->ajaxReturn($arr);
		}else{
			$arr['info']='修改失败';
			$arr['status']=0;
			$this->ajaxReturn($arr);
		}

	}

    public function actionEntrance($id){
    	$session = Yii::$app->session;
        if(!$session['account_info']){
            header("Location:".Url::toRoute("login"));
        }
        $account_info = $session['account_info'];

        $id = intval($_REQUEST['id']);

        if($account_info['allow_delivery'] == '1'){
            $w = " in(".implode(',', $account_info['location_ids']).")";
        }else{
            $w=" =".$account_info['location_ids'][0];
        }

        //门店名称
  		// $location_names = (new \yii\db\Query())
		// ->select('id, name')
		// ->from('fw_supplier_location')
		// ->where("id".$w)
		// ->all();
		$location_names = Yii::$app->db->createCommand('select id,name from fw_supplier_location where id'.$w)
           ->queryAll();
        // var_dump($location_names);die;
        if($account_info['allow_delivery']=='1'){
            $n_location_name='全部门店';
        }else{
            $n_location_name=$location_names[0]['name'];
        }
        $n_location_id='';

        if(!in_array($id,$account_info['location_ids'])){
            $id='';
        }else{
            $w=" =".$id;
            $n_location_id=$id;
            foreach ($location_names as $k => $v) {
                if($v['id']==$id){
                    $n_location_name=$v['name'];
                }
            }
        }

        if(!$n_location_id){
            $n_location_id=$account_info['location_ids'][0];
        }
	    return $this->render('entrance',['title'=>'车堂盛世-系统托管,解放老板','n_location_id'=>$n_location_id,'n_location_name'=>$n_location_name]);
    }
    public function actionentrance_more(){
        $id=intval($_REQUEST['id']);
        $this->assign("n_location_id",$id);
	    $this->display();
    }

	public function actionshop_count(){
		
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');
		
		$id=intval($_REQUEST['id']);

		
		$this->assign('title','车堂盛世-系统托管,解放老板');

		if($account_info['allow_delivery']=='1'){
			$w=" in(".implode(',', $account_info['location_ids']).")";
		}else{
			$w=" =".$account_info['location_ids'][0];
		}

		//门店名称
		$location_names=M()->query("select id,name from fw_supplier_location where id".$w);	

		if($account_info['allow_delivery']=='1'){
			$n_location_name='全部门店';
		}else{
			$n_location_name=$location_names[0]['name'];
		}
		$n_location_id='';

		if(!in_array($id,$account_info['location_ids'])){
			$id='';
		}else{
			$w=" =".$id;
			$n_location_id=$id;
			foreach ($location_names as $k => $v) {
				if($v['id']==$id){
					$n_location_name=$v['name'];
				}
			}
		}


	    //今日成交
	   $stime = strtotime(date('Y-m-d'));
	   $etime = $stime+86399;

	    
	  	//今日项目实收
	   $today_project_received=M('erp_order_deal as eod')->join('fw_erp_order as eo on eo.id=eod.order_id')->where(' eod.pay_time between '.$stime.' and '.$etime." and eod.mean_of_payment in('1','2','4','5') and eod.type='0' and eo.is_delete='0' and eo.location_id ".$w)->sum('price');
	   
	    $today_deal=$this->deal_common_sql("%Y-%m-%d",$stime,$etime,$w);	
	    
	    //普通销售套餐
		$package_today_deal=M('erp_package_order')->where('location_id '.$w.' and is_delete=0 and buy_time>='.$stime.' and buy_time<='.$etime)->sum('total_price');
		
		//全返套餐
		$back_package_today_deal=M('back_package_order')->where('location_id '.$w.' and status="1" and create_time>='.$stime.' and create_time<='.$etime)->sum('total_price');

		//今日会员卡充值
		$today_card_recharge=M('erp_card_recharge')->where(" create_time between ".$stime." and ".$etime." and location_id ".$w)->sum('total_price');;

		//今日售卡金额
		$card_today_price=M('erp_member_card')->where("status='1' and sale_location_id".$w." and supplier_id=".intval($account_info['supplier_id'])." and create_time between ".$stime." and ".$etime)->sum('total_price');		

		//今日实收汇总
		$today_received=price($today_project_received)+price($package_today_deal)+price($back_package_today_deal)+price($today_card_recharge)+price($card_today_price);

	    $project_today_deal['count']=intval($today_deal[date('Y-m-d')]['order_count']);
		$project_today_deal['total_price']=price($today_deal[date('Y-m-d')]['total_price'])+price($package_today_deal)+price($back_package_today_deal);
	  	
	  	//客户总数
		$member_count_sum=M('erp_member')->where('is_del=0 and location_id'.$w)->count();

		//今日新增客户
		$today_member_count=M('erp_member')->where('is_del=0 and location_id'.$w." and add_time>=".$stime." and add_time<".$etime)->count();

		$today_bind_count=M('supplier_fans')->where('num=-1 and is_fans=1 and supId'.$w." and addTime>=".$stime." and addTime<".$etime)->count();

		//本周交易的开始时间跟结束时间 
		$this_Mon=strtotime($this->this_monday(0,false));//strtotime("0 week this Monday");
		$this_Sun=strtotime($this->this_sunday(0,false))+86399;

		$this_week=$this->deal_common_sql("%Y-%m-%d",$this_Mon,$this_Sun,$w);
		
		foreach ($this_week as $k => $v) {
			$this_week_total+=$v['total_price'];
		}

		//本月普通销售套餐
		$package_this_week=M('erp_package_order')->where('location_id '.$w.' and buy_time>='.$this_Mon.' and buy_time<='.$this_Sun)->sum('total_price');
		
		//本月全返套餐
		$back_package_week_deal=M('back_package_order')->where('location_id '.$w.' and status="1" and create_time>='.$this_Mon.' and create_time<='.$this_Sun)->sum('total_price');

		$this_week_total+=price($package_this_week)+price($back_package_week_deal);

		//本月交易
		$this_moon_first=mktime(0,0,0,date('m'),1,date('Y'));
		$this_moon_end=mktime(23,59,59,date('m'),date('t'),date('Y'));
	
		$this_moon=$this->deal_common_sql("%m",$this_moon_first,$this_moon_end,$w);

		$this_moon_total=price($this_moon[date('m')]['total_price']);

		//本月普通销售套餐
		$package_today_moon=M('erp_package_order')->where('location_id '.$w.' and buy_time>='.$this_moon_first.' and buy_time<='.$this_moon_end)->sum('total_price');
		
		//本月全返套餐
		$back_package_moon_deal=M('back_package_order')->where('location_id '.$w.' and status="1" and create_time>='.$this_moon_first.' and create_time<='.$this_moon_end)->sum('total_price');

		$this_moon_total+=price($package_today_moon)+price($back_package_moon_deal);

		//总售卡金额
		$card_sum_total_price=M('erp_member_card')->where("status='1' and sale_location_id".$w." and supplier_id=".intval($account_info['supplier_id']))->sum('total_price');		

		$year=intval(date('Y')); 
		$for_month=intval(date('m'));
		$year_start_time = strtotime("1 January ".date('Y',time()));
		
		//数据初始化
		for ($i=0;$i<$for_month;$i++) {
			 $data1[$i]=0;	
			 $data2[$i]=0;
			 $data3[$i]=0;	
			 $data4[$i]=0;	
			 $data5[$i]=0;
			 $data6[$i]=0;
			 $data7[$i]=0;
			 $data8[$i]=0;
			 $data9[$i]=0;								
		}

		$month=array('01','02','03','04','05','06','07','08','09','10','11','12');

		//下单数量     
		$order_list=$this->deal_common_sql('%m',$year_start_time,time(),$w);

		foreach ($order_list as $k => $v) {
			$order[$k]['order_count']=$v['order_count'];
         	$order[$k]['order_time']=$k;
         	$order[$k]['total_price']=$v['total_price'];
		}

        $order=array_values($order);

		foreach ($data1 as $k => $v) {
			$s_k=array_search($order[$k]['order_time'],$month);				
			if($s_k>0||$s_k===0){
				$data1[$s_k]=$order[$k]['order_count'];
				$data2[$s_k]=$order[$k]['total_price'];	
			}			
		}

		//完成工单数量
		$order_data=implode(',',$data1);

		//销售金额走势
		$price_data=implode(',',$data2);


		//会员数量
        $member_sql="select FROM_UNIXTIME(add_time,'%m') as addtime,count(id) as member_count from fw_erp_member where location_id".$w." and is_del=0 and add_time>=".$year_start_time." group by addtime order by add_time asc";
		$member_count=M()->query($member_sql); 
         foreach ($data3 as $k => $v) {
			$s_k=array_search($member_count[$k]['addtime'],$month);

			if($s_k>0||$s_k===0){
				$data3[$s_k]=$member_count[$k]['member_count'];				
			}			
		}

		$member_data=implode(',',$data3);

		//扫码数量
        $bind_sql="select FROM_UNIXTIME(addTime,'%m') as addtime,count(id) as count from fw_supplier_fans where supId".$w." and num=-1 and is_fans=1 and addTime >=".$year_start_time." group by addtime order by addTime asc";
		$member_bind_count=M()->query($bind_sql); 
         foreach ($data9 as $k => $v) {
			$s_k=array_search($member_bind_count[$k]['addtime'],$month);

			if($s_k>0||$s_k===0){
				$data9[$s_k]=$member_bind_count[$k]['count'];				
			}			
		}

		$member_bind_data=implode(',',$data9);

		//售卡统计
		$card_sql="select FROM_UNIXTIME(create_time,'%m') as paytime,count(id) as card_count,sum(total_price) as total_price from fw_erp_member_card where status='1' and sale_location_id".$w." and supplier_id=".intval($account_info['supplier_id'])." and create_time>=".$year_start_time." group by paytime order by create_time asc";
        $card_count=M()->query($card_sql);
         foreach ($data4 as $k => $v) {
			$s_k=array_search($card_count[$k]['paytime'],$month);

			if($s_k>0||$s_k===0){
				$data4[$s_k]=$card_count[$k]['card_count'];
				$data5[$s_k]=$card_count[$k]['total_price'];				
			}			
		}

		$card_data=implode(',',$data4);
		$card_price=implode(',',$data5);

		//套餐销售数量及金额
		$package_sql="select FROM_UNIXTIME(buy_time,'%m') as time,count(id) as package_count,sum(total_price) as total_price from fw_erp_package_order where location_id".$w." and buy_time>=".$year_start_time." group by time order by buy_time asc";

		$package=M()->query($package_sql);

		//全返套餐数量及金额
		$back_package_sql="select FROM_UNIXTIME(create_time,'%m') as time,count(id) as package_count,sum(total_price) as total_price from fw_back_package_order where location_id ".$w." and status=1 and create_time>=".$year_start_time." group by time order by create_time asc";

		$back_package=M()->query($back_package_sql);

		$new_package=array_merge_recursive($package,$back_package);	
		foreach ($new_package as $k => $v) {
			$all_package[$v['time']]['time']=$v['time'];
			$all_package[$v['time']]['package_count']+=$v['package_count'];
			$all_package[$v['time']]['total_price']+=$v['total_price'];
		}		
		$all_package=array_values($all_package);
		foreach ($data6 as $k => $v) {
			$s_k=array_search($all_package[$k]['time'],$month);
			if($s_k>0||$s_k===0){
				$data6[$s_k]=$all_package[$k]['package_count'];
				$data7[$s_k]=$all_package[$k]['total_price'];				
			}			
		}

		// foreach ($data6 as $k => $v) {
		// 	$s_k=array_search($package[$k]['time'],$month);

		// 	if($s_k>0||$s_k===0){
		// 		$data6[$s_k]=$package[$k]['package_count'];
		// 		$data7[$s_k]=$package[$k]['total_price'];				
		// 	}			
		// }

		$package_data=implode(',',$data6);
		$package_price=implode(',',$data7);

		foreach ($data8 as $k => $v) {
			$data8[$k]=$data2[$k]+$data5[$k]+$data7[$k];
		}

		$total_price=implode(',',$data8);

		if(!$n_location_id){
			$n_location_id=$account_info['location_ids'][0];
		}

	   $this->assign('today_received',$today_received);
	   $this->assign("n_location_id",$n_location_id);
	   $this->assign("location_names",$location_names);
	   $this->assign("n_location_name",$n_location_name);
	   $this->assign("card_data",$card_data);
	   $this->assign("card_price",$card_price);
	   $this->assign('member_data',$member_data);
	   $this->assign('member_bind_data',$member_bind_data);
	   $this->assign('order_data',$order_data);
	   $this->assign('price_data',$price_data);
	   $this->assign('package_data',$package_data);
	   $this->assign('package_price',$package_price);
	   $this->assign('this_week_total',$this_week_total);
	   $this->assign('this_moon_total',$this_moon_total);
	   $this->assign('card_sum_total_price',$card_sum_total_price);
	   $this->assign('total_price',$total_price);
	   $this->assign('member_count_sum',$member_count_sum);
	   $this->assign('today_member_count',$today_member_count);
	   $this->assign('member_bind_count',$today_bind_count);
	   $this->assign('project_today_deal',$project_today_deal);
	   $this->display();
	}

	//这个星期的星期一 
	// @$timestamp ，某个星期的某一个时间戳，默认为当前时间 
	// @is_return_timestamp ,是否返回时间戳，否则返回时间格式 
	public function actionthis_monday($timestamp=0,$is_return_timestamp=true){ 
	    static $cache ; 
	    $id = $timestamp.$is_return_timestamp; 
	    if(!isset($cache[$id])){ 
	        if(!$timestamp) $timestamp = time(); 
	        $monday_date = date('Y-m-d', $timestamp-86400*date('w',$timestamp)+(date('w',$timestamp)>0?86400:-/*6*86400*/518400)); 
	        if($is_return_timestamp){ 
	            $cache[$id] = strtotime($monday_date); 
	        }else{ 
	            $cache[$id] = $monday_date; 
	        } 
	    } 
	    return $cache[$id]; 
	   
	} 

	//这个星期的星期天 
	// @$timestamp ，某个星期的某一个时间戳，默认为当前时间 
	// @is_return_timestamp ,是否返回时间戳，否则返回时间格式 
	public function actionthis_sunday($timestamp=0,$is_return_timestamp=true){ 
	    static $cache ; 
	    $id = $timestamp.$is_return_timestamp; 
	    if(!isset($cache[$id])){ 
	        if(!$timestamp) $timestamp = time(); 
	        $sunday = $this->this_monday($timestamp) + /*6*86400*/518400; 
	        if($is_return_timestamp){ 
	            $cache[$id] = $sunday; 
	        }else{ 
	            $cache[$id] = date('Y-m-d',$sunday); 
	        } 
	    } 
	    return $cache[$id]; 
	} 

	//shop_count公共方法
	private function deal_common_sql($sel_sql,$stime,$etime,$w){

		// $item_sql="select eo.id,eo.total_price,eo.total_original_price,eo.pay_amount,eo.pay_status,eoi.type,FROM_UNIXTIME(eo.bill_time,'".$sel_sql."') as billtime,eoi.sell_price,eoi.num,eoi.costs from fw_erp_order as eo  left join fw_erp_order_item as eoi on eoi.order_id=eo.id where eo.status='4' and eo.is_delete='0' and eo.location_id ".$w." and eo.bill_time>=".$stime." and eo.bill_time<=".$etime;
		// //订单详情数组
		// $item_deal=M()->query($item_sql);
		
		// foreach ($item_deal as $k => $v) {
		// 	$new_item_deal[$v['billtime']][$v['id']]['total_price']=$v['total_price'];//订单总金额				
		// 	$deal_ids[$v['billtime']][$v['id']]=$v['id'];
		// 	$has_ids[]=$v['id'];
						
		// }

		// $order_sql="select eo.id,eo.total_price,eo.total_original_price,eo.pay_amount,eo.pay_status,eod.mean_of_payment,eod.price,FROM_UNIXTIME(eod.pay_time,'".$sel_sql."') as paytime,FROM_UNIXTIME(eo.bill_time,'".$sel_sql."') as billtime from fw_erp_order as eo left join fw_erp_order_deal as eod on eod.order_id=eo.id where eo.status='4' and eo.is_delete='0'  and  eo.location_id ".$w." and ( (eod.pay_time>=".$stime." and eod.pay_time<=".$etime." and eod.type='0' and eo.pay_status='1') or (eod.pay_time>=".$stime." and eod.pay_time<=".$etime." and eod.type='0' and eo.bill_time>=".$stime." and eo.bill_time<=".$etime." and eo.pay_status='0') ) ";
		
		// //订单已支付明细数组
		// $order_deal=M()->query($order_sql);
		// foreach ($order_deal as $k => $v) {		
		// 	$new_order_deal[$v['paytime']][$v['id']]['total_price']=$v['total_price'];//订单总金额				
		// 	if(!in_array($v['id'],$has_ids))
		// 	$deal_ids[$v['paytime']][$v['id']]=$v['id'];
		// 	if($v['mean_of_payment']==8||$v['mean_of_payment']==9){
		// 		$new_order_deal[$v['paytime']][$v['id']]['offset_price']+=$v['price'];//抵扣金额
		// 	}
		// }			
		
		// //先提取挂单订单数据,再提取交易数组数据
		// foreach ($deal_ids as $key => $val) {
		// 	foreach ($val as $k => $v) {
		// 		$item_new_list[$key]['order_count']++;
		// 		if($new_item_deal[$key][$v]){//挂单的
		// 			$order_item=$new_item_deal[$key][$v];
		// 			$item_new_list[$key]['total_price']+=$order_item['total_price']-$new_order_deal[$key][$v]['offset_price'];//总金额				
		// 		}else{
		// 			$deal_item=$new_order_deal[$key][$v];
		// 			$item_new_list[$key]['total_price']+=$deal_item['total_price']-$deal_item['offset_price'];//总金额				
		// 		}
		// 	}
		// }
		$select_sql="select feo.id,feo.order_sn,feo.total_price,feo.pay_amount,feo.bill_time,feod.pay_time,feod.price,feod.mean_of_payment,FROM_UNIXTIME(feo.bill_time,'".$sel_sql."') as billtime,FROM_UNIXTIME(feod.pay_time,'".$sel_sql."') as paytime FROM fw_erp_order as feo left join fw_erp_order_deal as feod on feod.order_id=feo.id where feo.location_id ".$w." and feo.is_delete='0' and feod.type='0' and (feo.bill_time between ".$stime." and ".$etime." or feod.pay_time between ".$stime." and ".$etime.") or (feo.bill_time between ".$stime." and ".$etime." and feod.id is null and feo.location_id ".$w." and feo.is_delete='0' and feo.status='4')";
		
		$order_deal_list=M()->query($select_sql);
	
		//$item_new_list['order_count']=0;
		foreach ($order_deal_list as $k => $v) {
			//有挂单时间且在时间范围内
			if($v['bill_time']&&$v['bill_time']>=$stime&&$v['bill_time']<=$etime){
				if(!$deal_list[$v['billtime']][$v['id']]){
					$return_list[$v['billtime']]['order_count']++;
				}
				if($v['mean_of_payment']==8||$v['mean_of_payment']==9){
					$deal_list[$v['billtime']][$v['id']]['offset_price']+=$v['price'];
				}
				$deal_list[$v['billtime']][$v['id']]['total_price']=$v['total_price'];				
			}

			//无挂单时间
			if(!$v['bill_time']&&$v['pay_time']>=$stime&&$v['pay_time']<=$etime){
				if(!$deal_list[$v['paytime']][$v['id']]){
					$return_list[$v['paytime']]['order_count']++;
				}
				if($v['mean_of_payment']==8||$v['mean_of_payment']==9){
					$deal_list[$v['paytime']][$v['id']]['offset_price']+=$v['price'];
				}
				$deal_list[$v['paytime']][$v['id']]['total_price']=$v['total_price'];
				
			}
		}
		
		//除去项目抵扣金额及优惠券抵扣金额
		foreach ($deal_list as $key => $val) {
			foreach ($val as $k => $v) {
				$return_list[$key]['total_price']+=$v['total_price']-$v['offset_price'];
			}		
		}

	   return $return_list;
	}
	//销卡统计
	public function actionsell_card(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');
		
		$id=intval($_REQUEST['id']);


		$this->assign('title','车堂盛世-系统托管,解放老板');

		if($account_info['allow_delivery']=='1'){
			$w=" in(".implode(',', $account_info['location_ids']).")";
		}else{
			$w=" =".$account_info['location_ids'][0];
		}
		//门店名称
		$location_names=M()->query("select id,name from fw_supplier_location where id".$w);	
		
		if($account_info['allow_delivery']=='1'){
			$n_location_name='全部门店';
		}else{
			$n_location_name=$location_names[0]['name'];
		}
		
		$n_location_id='';

		if(!in_array($id,$account_info['location_ids'])){
			$id='';
		}else{
			$w=" =".$id;
			$n_location_id=$id;
			foreach ($location_names as $k => $v) {
				if($v['id']==$id){
					$n_location_name=$v['name'];
				}
			}
		}

		//var_dump($w);exit;
		$card_list=M('erp_member_card')->field('sum(total_price) as all_price,count(id) as sales,member_card_name')->where("status='1' and sale_location_id".$w." and supplier_id=".intval($account_info['supplier_id']))->group('card_id')->select();
		
		foreach ($card_list as $k => $v) {
			$card_type_count['card_name'].="'".$v['member_card_name']."',";
			$card_type_count['sales'].=$v['sales'].',';
			$card_type_count['price'].=$v['all_price'].',';		
		}
		$card_type_count['card_name']=substr($card_type_count['card_name'],0,strlen($card_type_count['card_name'])-1);
		$card_type_count['sales']=substr($card_type_count['sales'],0,strlen($card_type_count['sales'])-1); 
		$card_type_count['price']=substr($card_type_count['price'],0,strlen($card_type_count['price'])-1);
		
		$this->assign('card_type_count',$card_type_count);
		$this->assign("location_names",$location_names);
		$this->assign("n_location_name",$n_location_name);
		$this->assign("n_location_id",$n_location_id);
		$this->display();
	}

	/*//历史成交
	public function actionold_deal(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');
		//$location_id=$account_info['location_ids'][0];
		if($account_info['allow_delivery']=='1'){
			$w=" in(".implode(',', $account_info['location_ids']).")";
		}else{
			$w=" =".$account_info['location_ids'][0];
		}

		//历史成交
		$total_service=M('erp_order')->where("pay_status='1' and status='4' and location_id".$w)->sum('total_price');
		$total_card=M('erp_member_card')->where("status='1' and sale_location_id".$w)->sum('total_price');		

		$total_deal=$total_service+$total_card;
		$this->assign('total_deal',$total_deal);

		//今日成交
		$stime = strtotime(date('Y-m-d'));
	    $etime = $stime+86399;		
		$this_week_service=M('erp_order')->where("pay_status='1' and status='4' and pay_time>=".$stime." and pay_time<=".$etime." and location_id".$w)->sum('total_price');
		$this_week_card=M('erp_member_card')->where("status='1' and sale_location_id".$w." and pay_time>=".$stime." and pay_time<=".$etime)->sum('total_price');		

		$today_total=$this_week_service+$this_week_card;

		$this->assign('today_total',$today_total);
		$this->display();

	}*/

	public function actionproject_deal(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');

		$id=intval($_REQUEST['id']);


		$this->assign('title','车堂盛世-系统托管,解放老板');

		if($account_info['allow_delivery']=='1'){
			$w=" in(".implode(',', $account_info['location_ids']).")";
		}else{
			$w=" =".$account_info['location_ids'][0];
		}
		//门店名称
		$location_names=M()->query("select id,name from fw_supplier_location where id".$w);	

		if($account_info['allow_delivery']=='1'){
			$n_location_name='全部门店';
		}else{
			$n_location_name=$location_names[0]['name'];
		}
		$n_location_id='';

		if(!in_array($id,$account_info['location_ids'])){
			$id='';
		}else{
			$w=" =".$id;
			$n_location_id=$id;
			foreach ($location_names as $k => $v) {
				if($v['id']==$id){
					$n_location_name=$v['name'];
				}
			}
		}

		$stime = strtotime(date('Y-m-d'));
	    $etime = $stime+86399;

	
		$project_list=M('erp_order')->field('distinct fw_erp_order.id,eoi.sell_price,eoi.num,pay_time,epc.name,epc.top_id')->join('fw_erp_order_item as eoi on eoi.order_id=fw_erp_order.id')->join('fw_erp_goods as eg on eg.id=eoi.project_id')->join('fw_erp_product_category as epc on epc.id=eg.cate_id')->where("fw_erp_order.status='4' and fw_erp_order.is_delete='0' and fw_erp_order.location_id".$w)->select();
		
		$cate_list=M('erp_product_category')->where('is_del=0 and level=1')->select();
		foreach ($project_list as $k => $v) {
			if($v['pay_time']>=$stime&&$v['pay_time']<=$etime){
				$project_count[$v['top_id']]['today']+=$v['sell_price']*$v['num'];				
			}			
			$project_count[$v['top_id']]['total']+=$v['sell_price']*$v['num'];
		}
		foreach ($cate_list as $k => $v) {
					$cate_list[$k]['today']=0;
					$cate_list[$k]['total']=0;
			foreach ($project_count as $k1 => $v1) {				
				if($v['id']==$k1){
					$cate_list[$k]['today']=doubleval($v1['today']);
					$cate_list[$k]['total']=doubleval($v1['total']);
				}
			}
		}
		$this->assign('cate_list',$cate_list);
		$this->assign("location_names",$location_names);
		$this->assign("n_location_name",$n_location_name);
		$this->assign("n_location_id",$n_location_id);
		$this->display();
	}

	public function actionpay_type(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');

		$id=intval($_REQUEST['id']);
	
		$stime = strtotime(date('Y-m-d'));
	  	$etime = $stime+86399;

		if($account_info['allow_delivery']=='1'){
			$w=" in(".implode(',', $account_info['location_ids']).")";
		}else{
			$w=" =".$account_info['location_ids'][0];
		}
		$this->assign('title','车堂盛世-系统托管,解放老板');
		//门店名称
		$location_names=M()->query("select id,name from fw_supplier_location where id".$w);	

		if($account_info['allow_delivery']=='1'){
			$n_location_name='全部门店';
		}else{
			$n_location_name=$location_names[0]['name'];
		}
		$n_location_id='';

		if(!in_array($id,$account_info['location_ids'])){
			$id='';
		}else{
			$w=" =".$id;
			$n_location_id=$id;
			foreach ($location_names as $k => $v) {
				if($v['id']==$id){
					$n_location_name=$v['name'];
				}
			}
		}
		
		//付款方式统计 
	    $sql="SELECT feo.id,feod.price,feod.mean_of_payment,feo.total_price,feo.pay_status,feo.pay_amount,feo.bill_time,feod.pay_time,feod.type FROM fw_erp_order as feo left join fw_erp_order_deal as feod on feod.order_id=feo.id where location_id ".$w." and feo.status='4'  and  feo.is_delete='0'  and ( (  feod.type='0' ) or (feo.bill_time!=0))";

	    $pay_type=M()->query($sql);

       	for ($i=1; $i <10; $i++) {        		
			$pay_type_list[$i]['total_price']=0;	
			$pay_type_list[$i]['count']=0;
       	}

		foreach ($pay_type as $k => $v) { 								
			if($v){				
			
				$pay_type_list[$v['mean_of_payment']]['total_price']+=$v['price'];
				$pay_type_list[$v['mean_of_payment']]['count']++;	
				if(!$v['pay_status']&&!$pay_type_status[$v['id']]){	
					$pay_type_list[7]['total_price']+=$v['total_price']-$v['pay_amount'];
					$pay_type_list[7]['count']++;	
					$pay_type_status[$v['id']]=1;	
				}	
													
			}						
		}
		

    	$this->assign('ptl',$pay_type_list);
    	$this->assign("location_names",$location_names);
    	$this->assign("n_location_name",$n_location_name);
    	$this->assign("n_location_id",$n_location_id);
		$this->display();
	}

	//今日付款方式统计
	public function actiontoday_pay_type(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');		
		$id=intval($_REQUEST['id']);
		
		if($account_info['allow_delivery']=='1'){
			$w=" in(".implode(',', $account_info['location_ids']).")";
		}else{
			$w=" =".$account_info['location_ids'][0];
		}
		$this->assign('title','车堂盛世-系统托管,解放老板');
		//门店名称
		$location_names=M()->query("select id,name from fw_supplier_location where id".$w);	

		if($account_info['allow_delivery']=='1'){
			$n_location_name='全部门店';
		}else{
			$n_location_name=$location_names[0]['name'];
		}
		$n_location_id='';

		if(!in_array($id,$account_info['location_ids'])){
			$id='';
		}else{
			$w=" =".$id;
			$n_location_id=$id;
			foreach ($location_names as $k => $v) {
				if($v['id']==$id){
					$n_location_name=$v['name'];
				}
			}
		}

        $stime = strtotime(date('Y-m-d'));
	    $etime = $stime+86399;

		//今日付款方式统计 
	   	$sql="SELECT feo.id,feod.price,feod.mean_of_payment,feo.total_price,feo.pay_status,feo.pay_amount,feo.bill_time,feod.pay_time,feod.type FROM fw_erp_order as feo left join fw_erp_order_deal as feod on feod.order_id=feo.id where location_id ".$w." and feo.status='4'  and  feo.is_delete='0'  and ( (  feod.type='0' ) or (feo.bill_time!=0))";

	    $today_pay_type=M()->query($sql);

	    for ($i=1; $i <10; $i++) {        		
			$today_pay_type_list[$i]['total_price']=0;	
			$today_pay_type_list[$i]['count']=0;
       	}

		foreach ($today_pay_type as $k => $v) { 								
			if($v){				
				//今日支付
				if($v['pay_time']>=$stime&&$v['pay_time']<=$etime){	
					$today_pay_type_list[$v['mean_of_payment']]['total_price']+=$v['price'];
					$today_pay_type_list[$v['mean_of_payment']]['count']++;					
						
				}
				//今日挂单
				if($v['bill_time']>=$stime&&$v['bill_time']<=$etime){
					if(!$v['pay_status']&&!$today_pay_type[$v['id']]){
						$today_pay_type_list[7]['total_price']+=$v['total_price']-$v['pay_amount'];
						$today_pay_type_list[7]['count']++;	
						$today_pay_type[$v['id']]=1;
					}
				}										
			}						
		}
		
	
    	$this->assign('tptl',$today_pay_type_list);
    	$this->assign("location_names",$location_names);
    	$this->assign("n_location_name",$n_location_name);
    	$this->assign("n_location_id",$n_location_id);
		$this->display();
	}

 	//月度计划
 	public function actionmonth_plan(){
 		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');

		$id=intval($_REQUEST['id']);

		if($account_info['allow_delivery']=='1'){
			$w=" in(".implode(',', $account_info['location_ids']).")";
		}else{
			$w=" =".$account_info['location_ids'][0];
		}
		$this->assign('title','车堂盛世-系统托管,解放老板');
		//门店名称
		$location_names=M()->query("select id,name from fw_supplier_location where id".$w);	

		if($account_info['allow_delivery']=='1'){
			$n_location_name='全部门店';
		}else{
			$n_location_name=$location_names[0]['name'];
		}
		$n_location_id='';

		if(!in_array($id,$account_info['location_ids'])){
			$id='';
		}else{
			$w=" =".$id;
			$n_location_id=$id;
			foreach ($location_names as $k => $v) {
				if($v['id']==$id){
					$n_location_name=$v['name'];
				}
			}
		}

		$years=isset($_REQUEST['years'])?intval($_REQUEST['years']):date('Y',time());
		if(isset($_REQUEST['months'])){
			if($_REQUEST['months']<10){
				$months='0'.intval($_REQUEST['months']);
			}else{
				$months=intval($_REQUEST['months']);
			}
		}else{
			$months=date('m',time());
		}
		$t_months=date('Ym',time());
		$today=date('Ymd',time());

		//获得cate_id数组

		$cate_list=M()->query("select id from fw_erp_product_category where level=1");
		foreach ($cate_list as $k => $v) {
			$cate_ids[$k]=$v['id'];
		}
		//初始化数据
		foreach($cate_ids as $v){
			$today_val[$v]=0;
			$actual_val[$v]=0;
			$plan_val[$v]=0;
			$n_cate_ids[$v]=$v;
		}

		//如果是当年当月则显示当天数据
		if($t_months==($years.$months)){
			$today_plan=M()->query("select today_val,cate_id from fw_erp_month_plan where today=".$today." and location_id".$w);				
			if($today_plan){
				foreach ($today_plan as $k => $v) {
					$s_k=array_search($v['cate_id'],$n_cate_ids);
					$today_val[$s_k]+=price($v['today_val']);
				}
			}
		}
		
		$plan=M()->query("select actual_val,plan_val,cate_id from fw_erp_month_plan where years=".$years." and months=".$months." and location_id".$w);
						
		if($plan){
			foreach ($plan as $k => $v) {
				$s_k=array_search($v['cate_id'],$n_cate_ids);
				$actual_val[$s_k]+=price($v['actual_val']);
				$plan_val[$s_k]+=price($v['plan_val']);
			}
		}

		//初始化数据
		$plan_sum_val[0]=$plan_sum_val[1]=0;

		//统计整月完成金额

		$plan_sum=M()->query("select sum(plan_val) as all_plan_val,sum(actual_val) as all_actual_val from fw_erp_month_plan where years=".$years." and months=".$months." and location_id".$w);

		if(!$plan_sum[0]['all_plan_val']){
			$plan_sum[0]['all_plan_val']=0;
		}

		if(!$plan_sum[0]['all_actual_val']){
			$plan_sum[0]['all_actual_val']=0;
		}

		$plan_sum_val[0]=$plan_sum[0]['all_actual_val'];

		if($plan_sum[0]['all_plan_val']-$plan_sum[0]['all_actual_val']>=0){
			$plan_sum_val[1]=$plan_sum[0]['all_plan_val']-$plan_sum[0]['all_actual_val'];
		}else{
			$plan_sum_val[1]=0;
		}


		$this->assign("cate_ids",$cate_ids);
		$this->assign("select_years",$years);
		$this->assign("select_months",$months);
		$this->assign("today_val",$today_val);
		$this->assign("actual_val",$actual_val);
		$this->assign("plan_val",$plan_val);
		$this->assign("plan_sum_val",$plan_sum_val);
    	$this->assign("location_names",$location_names);
    	$this->assign("n_location_name",$n_location_name);
    	$this->assign("n_location_id",$n_location_id);
		$this->display();
	}

	public function actionerp_verify(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$this->assign('title','预约验证');
		$this->display();
	}

	public function actionerp_ajax_verify(){
		$account_info=session('account_info');
		if(!$account_info['id']){
			$result['status'] = 0;
			$result['msg'] = '请重新登录';
			$this->ajaxReturn($result);
		}
		$type=intval($_REQUEST['type']);
		if($type!=1&&$type!=2){
			$result['status'] = 0;
			$result['msg'] = '非法操作';
			$this->ajaxReturn($result);
		}
		if($type==1){				
			$sn = trim($_REQUEST['sn']);
			session('sn','');	
		}else{
			$sn=session('sn');
		}

		$supplier_id = intval($account_info['supplier_id']);

		$coupon_data=M('erp_coupon as ec')->join('fw_erp_order as eo on eo.id=ec.order_id')->join('fw_erp_order_item as eoi on eoi.order_id=eo.id')->join('fw_supplier_location as sl on sl.id=eo.location_id')->field('eo.id as order_id,ec.id as coupon_id,eo.order_sn,eo.total_price,eo.car_sn,eoi.project_name,sl.name,eo.advance_time,sl.id as sid,sl.supplier_id,ec.confirm_time')->where('eo.type="3" and eo.pay_status="1" and ec.is_del=0 and ec.sn="'.$sn.'"')->find();
	
		if($coupon_data)
			{
				
				if(!in_array($coupon_data['sid'],$account_info['location_ids']))
				{
					$result['status'] = 0;
					$result['msg'] = '没有门店权限管理该数据';
					$this->ajaxReturn($result);
				}

				if($coupon_data['supplier_id']!=$supplier_id)
				{
					$result['status'] = 0;
					$result['msg'] = '该券为其他团购商户的团购券，不能确认';
					$this->ajaxReturn($result);
				}
				elseif($coupon_data['confirm_time'] > 0)
				{
					if($type==1){
						$result['status'] =1;
						$this->assign('cd',$coupon_data);
						$html=$this->fetch('Biz/sn_info');
						$result['msg'] = $html;	
						$this->ajaxReturn($result);
					}else{
						$result['status'] = 0;
						$result['msg'] ='该券已于'.date('Y-m-d H:i:s',$coupon_data['confirm_time']).'使用';
						$this->ajaxReturn($result);
					}
				
				}
				else
				{					
					
					if($type==1){
						$result['status'] = 1;	
						session('sn',$sn);
						$this->assign('cd',$coupon_data);
						$html=$this->fetch('Biz/sn_info');
						$result['msg'] = $html;	
						$this->ajaxReturn($result);
					}else{
						$data['confirm_user_id']=$account_info['id'];
						$data['confirm_time']=time();
						
						M('erp_coupon')->where(array('id'=>intval($coupon_data['coupon_id'])))->save($data);

						$order_data['status']='1';
						M('erp_order')->where(array('id'=>intval($coupon_data['order_id'])))->save($order_data);

						$result['status'] = 1;
						$result['msg'] = '<p class="btn-info" style="margin:0">该券已于'.date('Y-m-d H:i:s',time()).'使用</p>';
						$this->ajaxReturn($result);
					}
				
				}
			}
			else
			{		
					$result['status'] = 0;
					$result['msg'] = '无效的验证码';	
					$this->ajaxReturn($result);
			}
		
	}

	//查看员工提成
	public function actionemployee_commission(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');

		$id=intval($_REQUEST['id']);

		if($account_info['allow_delivery']=='1'){
			$w=" in(".implode(',', $account_info['location_ids']).")";
		}else{
			$w=" =".$account_info['location_ids'][0];
		}
		$this->assign('title','车堂盛世-系统托管,解放老板');
		//门店名称
		$location_names=M()->query("select id,name from fw_supplier_location where id".$w);	

		if($account_info['allow_delivery']=='1'){
			$n_location_name='全部门店';
		}else{
			$n_location_name=$location_names[0]['name'];
		}
		$n_location_id='';

		if(!in_array($id,$account_info['location_ids'])){
			$id='';
		}else{
			$w=" =".$id;
			$n_location_id=$id;
			foreach ($location_names as $k => $v) {
				if($v['id']==$id){
					$n_location_name=$v['name'];
				}
			}
		}

		$month_first_day=mktime(0,0,0,date('m'),1,date('Y'));//月初
		$month_now_day=time();//当前时间
		$start_time=htmlspecialchars(addslashes(trim($_REQUEST['start_time'])));
		$end_time=htmlspecialchars(addslashes(trim($_REQUEST['end_time'])));
		$employee_id=intval($_REQUEST['employee_id']);

		$start_time=$start_time==''?$month_first_day:$start_time;
		$end_time=$end_time==''?$month_now_day:$end_time;

		$t_start_time=strtotime($start_time);
		$t_end_time=strtotime($end_time);
		if($_REQUEST['is_redirect']==1)
		{
			redirect(U('Biz/employee_commission',array("id"=>$n_location_id,"start_time"=>$t_start_time,"end_time"=>$t_end_time,"employee_id"=>$employee_id)));
		}

		if(isset($start_time)&&is_numeric($start_time)&&$start_time!=0&&isset($end_time)&&is_numeric($end_time)&&$end_time!=0){	
			$month_first_day=$start_time;
			$month_now_day=$end_time+60*60*24;			 
		}

		//如果是员工账号登录则只能看自己的提成
		$has_employee=M("erp_employees")->field("id,u_name")->where("account_id=".$account_info['id'])->find();

		if($has_employee){
			$t=" and ee.id=".$has_employee['id']." and eec.employee_id=".$has_employee['id'];
			//员工列表只包括自己
			$employee_list=array(array("id"=>$has_employee['id'],"u_name"=>$has_employee['u_name']));
		}else{
			if($employee_id>0){//搜索
				$t=" and ee.id=".$employee_id." and eec.employee_id=".$employee_id;		
			}
			//员工列表
			$employee_list=M()->query("select id,u_name from fw_erp_employees where status_type=0 and store_id".$w);
		}

		//提成列表
		$commission_list=M()->query("select eec.type,eec.money from fw_erp_employees_commission as eec left join fw_erp_employees as ee on ee.id=eec.employee_id where ee.status_type=0 and ee.store_id".$w." and eec.location_id".$w." and eec.money>0 and eec.type in(0,1,2,3) and eec.add_time between ".$month_first_day." and ".$month_now_day.$t);
		//初始化
		$commission=array('sale'=>0,'construction'=>0,'sale_card'=>0,'sale_package'=>0,'total_price'=>0);

		foreach ($commission_list as $k => $v) {
			if($v['type']==0){
				$commission['sale']+=$v['money'];
			}elseif ($v['type']==1) {
				$commission['construction']+=$v['money'];
			}elseif ($v['type']==2) {
				$commission['sale_card']+=$v['money'];
			}elseif ($v['type']==3) {
				$commission['sale_package']+=$v['money'];
			}
			$commission['total_price']+=$v['money'];
		}

		$this->assign("employee_list",$employee_list);
		$this->assign("employee_id",$employee_id);
		$this->assign("commission",$commission);
		$this->assign("start_time",$start_time);
		$this->assign("end_time",$end_time);
		$this->assign("location_names",$location_names);
    	$this->assign("n_location_name",$n_location_name);
    	$this->assign("n_location_id",$n_location_id);
		$this->display();
	}


	//收入列表
	public function actionincome(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}

		$pms_supplier=session('account_info');		
		$location_id=intval($pms_supplier['location_ids'][0]);

		if(!M('agent_qrcode')->where('type=2 and shop_id='.$location_id)->find()){
			$this->error('您还不是合伙人',U('Biz/shop_count'),3);
		}

		$commission_list=M('pms_shop_commission')->field('commission,create_time,is_settlement')->where('shop_type=2 and shop_id='.intval($location_id))->select();
		

		//今日
		$stime = strtotime(date('Y-m-d'));
	   	$etime = $stime+86399;

		//昨日
		$lastday_first=strtotime(date("Y-m-d",strtotime("-1 day")));
		$lastday_end=$stime;

		//今年
		$thisyear_first=strtotime(date('Y').'-1-1');
		$thisyear_end=$stime;
		
		//当月
	   	$thismoon_first=mktime(0,0,0,date('m'),1,date('Y'));
		$thismoon_end=mktime(23,59,59,date('m'),date('t'),date('Y'));

		//上月
		$lastmoon_first = date('Y-m-d', mktime(0,0,0,date('m')-1,1,date('Y'))); //上个月的开始日期
		$lastmoon_end = date('Y-m-d', mktime(0,0,0,date('m')-1,$t,date('Y'))); //上个月的结束日期
		

		foreach ($commission_list as $k => $v) {
			$time=$v['create_time'];
			$commission['total']+=$v['commission'];
			if($v['is_settlement']){
				$commission['settled']+=$v['commission'];
			}else{
				$commission['not_settled']+=$v['commission'];
			}
			if($time>=$stime&&$time<=$etime){
				$commission['today']+=$v['commission'];
			}
			if($time>=$lastday_first&&$time<=$lastday_end){
				$commission['lastday']+=$v['commission'];
			}
			if($time>=$thisyear_first){
				$commission['thisyear']+=$v['commission'];
			}
			if($time>=$thismoon_first&&$time<=$thismoon_end){
				$commission['thismoon']+=$v['commission'];
			}
			if($time>=$lastmoon_first&&$time<=$lastmoon_end){
				$commission['lastmoon']+=$v['commission'];
			}
		}
		$this->assign('title','代理收入');
		$this->assign('commission',$commission);
		$this->assign('location_info',M('supplier_location')->field('name,preview')->where('id='.$location_id)->find());
		$this->display();
	}

	//发展门店列表
	public function actionlocation(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}

		$pms_supplier=session('account_info');
		
		$location_id=intval($pms_supplier['location_ids'][0]);

		if(!M('agent_qrcode')->where('type=2 and shop_id='.$location_id)->find()){
			$this->error('您还不是合伙人',U('Biz/entrance'),3);
		}

		//$month_first_day=mktime(0,0,0,date('m'),1,date('Y'));//月初
		//$month_now_day=time();//当前时间
		$start_time=htmlspecialchars(addslashes(trim($_REQUEST['start_time'])));
		$end_time=htmlspecialchars(addslashes(trim($_REQUEST['end_time'])));
		$employee_id=intval($_REQUEST['employee_id']);

		$start_time=$start_time==''?$month_first_day:$start_time;
		$end_time=$end_time==''?$month_now_day:$end_time;

		$t_start_time=strtotime($start_time);
		$t_end_time=strtotime($end_time);
		if($_REQUEST['is_redirect']==1)
		{
			redirect(U('Biz/location',array("id"=>$n_location_id,"start_time"=>$t_start_time,"end_time"=>$t_end_time,"employee_id"=>$employee_id)));
		}

		if(isset($start_time)&&is_numeric($start_time)&&$start_time!=0&&isset($end_time)&&is_numeric($end_time)&&$end_time!=0){	
			$month_first_day=$start_time;
			$month_now_day=$end_time+60*60*24;
			$where.=" and fao.pay_time between ".$month_first_day." and ".$month_now_day;	 
		}
		
		if($employee_id&&$pms_supplier['allow_delivery']==1){
			$where.=" and af.shop_user_id=".$employee_id;
		}elseif($employee_id){
			$where.=" and af.shop_user_id=".$pms_supplier['id'];
		}


		//门店信息
		$location_info=M('supplier_location')->field('name,preview')->where('id='.$location_id)->find();

		$location=M('agent_fans as af')->field('fsl.name,fsl.address,fsl.mobile,fsa.account_name')->join('fw_agent_order as fao on fao.open_id=af.open_id')->join('fw_supplier_account as fsa on fsa.id=af.shop_user_id')->join('fw_supplier_location as fsl on fsl.id=af.location_id')->where('af.shop_type=2 and af.shop_id='.intval($location_id)." and af.location_id!=''".$where)->select();

		$count=M('agent_fans as af')->join('fw_agent_order as fao on fao.open_id=af.open_id')->join('fw_supplier_account as fsa on fsa.id=af.shop_user_id')->join('fw_supplier_location as fsl on fsl.id=af.location_id')->where('af.shop_type=2 and af.shop_id='.intval($location_id)." and af.location_id!=''".$where)->count();

		foreach ($location as $k => $v) {
			if(!$v['account_name']){
				$location[$k]['account_name']=$location_info['name'];
			}
		}

		//员工列表
		if($pms_supplier['allow_delivery']==1){
			$employee_list=M('erp_employees ')->field('id,u_name')->where('status_type=0 and store_id='.$location_id)->select();
		}
	
		$this->assign('pms_supplier',$pms_supplier);
		$this->assign('count',$count);
		$this->assign('price',$count*500);
		$this->assign("employee_id",$employee_id);
		$this->assign("employee",$employee_list);
		$this->assign("commission",$commission);
		$this->assign("start_time",$start_time);
		$this->assign("end_time",$end_time);
		$this->assign('title','发展门店');
		$this->assign('location',$location);
		$this->assign('location_info',$location_info);
		$this->display();
	}

	//推广
	public function actionmyagent(){

		// $agent_code=$_GET['code'];

		// if($agent_code){

		// 		//员工帐号id及类型数组
		// 		$info=explode('_',passport_decrypt($agent_code,'17cct_com_supplier_agent_key'));	
			  	
		// 	  	//type=1为供应商,2为门店
		// 	  	if($info[1]==1){
		// 	  		$pms_supplier=M('pms_supplier as ps')->field('ps.id,ps.name,fpsa.a_name,fpsa.img')->join('fw_pms_supplier_account as fpsa on fpsa.supplier_id=ps.id')->where('fpsa.id='.$info[0])->find();	
			  		
		// 	  	}else{
		// 	  		$pms_supplier=M('supplier_location as sl')->field('sl.id,sl.name,fsa.account_name as a_name')->join('fw_supplier_account_location_link as fsall on fsall.location_id = sl.id')->join('fw_supplier_account as fsa on fsa.id=fsall.account_id')->where('fsall.account_id='.$info[0])->find();
		// 	  	}

		// 	  	if(!$pms_supplier){
		// 	  		$this->error('该链接为无效链接',U('Index/index'),3);
		// 	  	}
			  
		// 	  	//门店二维码信息
		// 	  	$qrcode_info=M('agent_qrcode')->where('shop_id='.$pms_supplier['id']." and type=".$info[1])->find();

		// 	  	import('@.ORG.Wechat');
		// 		if(!$this->wxAPI)
		// 		$this->wxAPI = new Wechat();

		// 		//二维码id
		// 		$open_id=session('oprate_opend_id');
				
		// 		//微信token
		// 		$token = getshopAccToken();

		// 	  	//获取用户微信基本信息
		// 	    $user_data=$this->wxAPI->get_wx_info($open_id,$token);	   	  		  			 
			    
		//   		$fans=M('agent_fans')->where("open_id='".$open_id."'")->find();

		//   		if($fans==null&&$open_id)//首次访问
		//   		{	
		//   			$d['open_id']=$open_id;
		//   			$d['qrcode_id']=$qrcode_info['id'];
		// 	  		$d['shop_user_id']=$info[0];
		// 			$d['shop_id']=intval($pms_supplier['id']);		
		// 			$d['shop_type']=intval($info[1]);
		// 			$d['location_id']=0;
		// 			$d['is_vip']=0;
		// 			$d['add_time']=time();
		// 			if($user_data['nickname'])
		//   			$d['nickname']=$user_data['nickname'];  			
		  				
		//   			M('agent_fans')->add($d);	  			
		//   		}else{
		//   			if($fans['is_submit']==0&&$open_id){
		//   				//更新扫描信息 	
		// 	  			$d['shop_user_id']=$info[0];
		// 	  			$d['qrcode_id']=$qrcode_info['id'];
		// 				$d['shop_id']=intval($pms_supplier['id']);		
		// 				$d['shop_type']=intval($info[1]);
		// 				$d['add_time']=time();
		// 				if($user_data['nickname'])
		// 	  			$d['nickname']=$user_data['nickname'];  
		// 	  			M('agent_fans')->where('id='.$fans['id'])->save($d);
		//   			}	  			
		//   		}		

		//   	$qrcode=$qrcode_info['qrcode'];

		// }else{

			if(!session('account_info')){
				header("Location:".U("Biz/login"));
			}

			//登录信息
			$pms_supplier=session('account_info');
		
			$location_id=intval($pms_supplier['location_ids'][0]);

			if(!M('agent_qrcode')->where('type=2 and shop_id='.$location_id)->find()){
				$this->error('您还不是合伙人',U('Biz/entrance'),3);
			}
			//门店二维码
			$qrcode=M('agent_qrcode')->where('type=2 and shop_id='.$location_id)->getField('qrcode');	

			$location_name=M('supplier_location')->where('id='.$location_id)->getField('name');
		
			$key="17cct_com_supplier_agent_key";

			$agent_code=trim(passport_encrypt($pms_supplier['id'].'_2',$key));
		
		//}

		$qrcode_shop_count=intval(M('agent_qrcode')->count())+1000;
		//已加入门店数,不够五位用0补充
		$shop_count=str_split(sprintf("%05d",$qrcode_shop_count));

		$nonceStr=createNonceStr();
		$this->assign('nonceStr',$nonceStr);//随机串
		$time=time();	
	    $ticket=get_jsdk_ticket();
	    $config_sign=sha1("jsapi_ticket=".$ticket."&noncestr=".$nonceStr."&timestamp=".$time."&url=".'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	    $this->assign('time',$time);
	    $this->assign('sign',$config_sign);	  
	    $this->assign('pms_supplier',$pms_supplier);
	    $this->assign('qrcode',$qrcode);
	    $this->assign('location_name',$location_name);
	    $this->assign('agent_code',$agent_code);		 
	    $this->assign('shop_count',$shop_count);
	    $this->assign('title','代理推广');
		$this->display();
	}

	//门店晒单
	public function actionshaidan(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');

		$id=intval($_REQUEST['id']);
	
		if($account_info['allow_delivery']=='1'){
			$w=" in(".implode(',', $account_info['location_ids']).")";
		}else{
			$w=" =".$account_info['location_ids'][0];
		}
		$this->assign('title','车堂盛世-系统托管,解放老板');
		//门店名称
		$location_names=M()->query("select id,name from fw_supplier_location where id".$w);	

		if($account_info['allow_delivery']=='1'){
			$n_location_name='全部门店';
		}else{
			$n_location_name=$location_names[0]['name'];
		}
		$n_location_id='';

		if(!in_array($id,$account_info['location_ids'])){
			$id='';
		}else{
			$w=" =".$id;
			$n_location_id=$id;
			foreach ($location_names as $k => $v) {
				if($v['id']==$id){
					$n_location_name=$v['name'];
				}
			}
		}

		//一级分类
		$cate_list = M()->query("SELECT id,pid,name FROM fw_deal_cate WHERE is_delete=0 AND is_effect=1 AND is_show=1 ORDER BY sort DESC");

		//品牌列表
		$brand_list = M()->query("SELECT id,name FROM fw_brand ORDER BY sort DESC");

    	$this->assign("location_names",$location_names);
    	$this->assign("n_location_name",$n_location_name);
    	$this->assign("n_location_id",$n_location_id);
    	$this->assign("cate_list",$cate_list);
    	$this->assign("brand_list",$brand_list);
		$this->display();
	}

	//获得二级分类
	public function actionget_cate2_list(){

		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}

		$cate1_id = intval($_POST['cate1_id']);

		if($cate1_id){
			$category_list = M()->query("SELECT deal_cate_type_id,name FROM fw_deal_cate_type_link AS dctl INNER JOIN fw_deal_cate_type AS dct ON dctl.deal_cate_type_id=dct.id WHERE dctl.cate_id = ".$cate1_id." ORDER BY sort DESC");

			foreach ($category_list as $k => $v) {
				$html.="<option value=".$v['deal_cate_type_id'].">".$v['name']."</option>";
			}
			$this->ajaxReturn($html);
		}else{
			$html="<option value='0'>二级分类</option>";
			$this->ajaxReturn($html);
		}
	}

	//保存晒单信息
	public function actionshaidan_save(){

		$account_info = session('account_info');
		$store_id = intval($_POST['store_id']);
		$sd_imgs = explode(',', trim($_POST['sd_imgs']));

		$data['title'] = trim($_POST['title']);
		$data['brand_id'] = intval($_POST['brand_id']);
		$data['uid'] = intval($account_info['id']);
		$data['pubtime'] = time();
		$data['cate_id'] = intval($_POST['cate1_id']);
		$data['categoryid'] = intval($_POST['cate2_id']);
		$data['city_id'] = intval(M('supplier_location')->where(array('id'=>$store_id))->getField('city_id'));
		$data['user_type'] = 2;
		$data['is_show'] = 1;
		$data['supid'] = $store_id;
		$data['detail'] = '<p>'.trim($_POST['content']).'</p>';
		$data['thumb'] = $sd_imgs[0];

		foreach ($sd_imgs as $k => $v) {
			if($v){
				$data['detail'] .= '<p><img src="'.$v.'!detail"/></p>';
			}
		}

		$result = M('shaidan')->add($data);

		if($result)
			$this->ajaxReturn(array('status'=>1,'msg'=>'晒单成功'));
		else
			$this->ajaxReturn(array('status'=>0,'msg'=>'晒单失败'));
		
	}

	public function actionpurchase_order(){
		$t=intval($_REQUEST['t'])==0?1:intval($_REQUEST['t']);
		$count = $this->get_count();
		$this->assign('count',$count);
		$this->assign('t',$t);
		$this->assign('title','诚车堂-订货管理小助手！');
		$this->display();
	}

	/**
	* 获得各状态订单数量
	**/
	private function get_count(){

		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');
		$location_id=$account_info['location_ids'][0];

		//各状态订单数量
		$order_list = M('pms_order')->where('location_id='.$location_id.' and system=0 ')->select();
		$order_merge_list = M('pms_merge_order')->where('location_id='.$location_id." and pay_status=0 and system=0 ")->select();	
		
		$count = array('paid'=>0,'nopay'=>0,'noconfirm'=>0,'canceled'=>0,'uncommitted'=>0);

		if($order_list){
			foreach ($order_list as $k => $v) {
				if(((in_array($v['means_of_payment'], array(1,2)) && in_array($v['pay_status'], array(1,2))) || ($v['pay_status']==2 && in_array($v['means_of_payment'], array(3,4,5,6)))) && $v['is_del'] == 0 && $v['pay_time'] > 0){
					$count['paid'] ++;
				}
				if(in_array($v['means_of_payment'], array(3,4,5,6)) && $v['pay_status'] == 1 && $v['is_del'] == 0 && $v['pay_time'] > 0){
					$count['nopay'] ++;
				}	
				if(($v['status'] == 2 or ($v['type'] == 2 && $v['purchase_user_id'] == 0)) && $v['is_del'] == 0 && $v['pay_time'] > 0){
					$count['noconfirm'] ++;
				}
			}
		}
				
		if($order_merge_list){
			foreach ($order_merge_list as $kk => $vv) {
				if($vv['is_del'] == 0){
					$count['uncommitted'] ++;
				}
				if($vv['is_del'] == 1){
					$count['canceled'] ++;
				}
				
			}
		}
		return $count;

	}

	public function actionajax_get_purchase(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$account_info=session('account_info');
		$location_id=$account_info['location_ids'][0];
		$type=intval($_REQUEST['t']);//1为已付款，2为未付款 3为未确认，4为已取消
		$page=intval($_REQUEST['p']);
		$limit=($page*8).",8";

		if($type == 1 || $type == 2 || $type ==5){
			if($type == 1){
				$w = " and ((means_of_payment in(1,2) and pay_status in(1,2)) or (means_of_payment in(3,4,5,6) and pay_status=2))";
			}
			if($type == 2){
				$w = " and means_of_payment in (3,4,5,6) and pay_status=1";
			}
			if($type == 5){
				$w = " and ((type=2 and purchase_user_id=0) or status=2)";
			}	

			$order=M('pms_order')->where('is_del=0 and system=0 and  pay_time>0 and location_id='.$location_id.$w)->order('id desc')->limit($limit)->select();

			foreach ($order as $k => $v) {
				$order[$k]['order_status']=$this->get_order_status($v['status']);
			}

		}
		if($type == 3 || $type == 4){
			if($type == 3){
				$w = " and is_del=0";
			}else{
				$w = " and is_del=1";
			}

			$order=M('pms_merge_order')->where('location_id='.$location_id.' and pay_status=0 and system=0 '.$w)->order('id desc')->limit($limit)->select();
		}
				
		$this->assign('order',$order);
		$this->assign('type',$type);
		echo $html=$this->fetch();
	}

	

	public function actionpurchase_detail(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$id=intval($_REQUEST['id']);
		$account_info=session('account_info');
		$location_id=$account_info['location_ids'][0];

		$goods=M('pms_order_item as poi')->field('poi.goods_name,poi.goods_id,poi.sell_price,poi.num,poi.thumbnail,pg.supplier_id,pg.unit,fps.name,fps.mobile,fpo.*')->join('fw_pms_goods as pg on pg.id=poi.goods_id')->join('fw_pms_supplier as fps on fps.id=pg.supplier_id')->join('fw_pms_order as fpo on fpo.id=poi.order_id')->where('poi.order_id='.$id.' and fpo.system=0 and fpo.location_id='.$location_id)->select();

		if(!$goods){
			$this->error('无此订单信息',U('Biz/purchase_order'),3);
		}
		foreach ($goods as $k => $v) {
			$goods_list[$v['supplier_id']]['supplier_name']=$v['name'];
			$goods_list[$v['supplier_id']]['mobile']=$v['mobile'];
			$goods_list[$v['supplier_id']]['goods'][]=$v;
		}
		$info['order_sn']=$v['order_sn'];
		$info['create_time']=$v['create_time'];
		$info['pay_time']=$v['pay_time'];
		$info['status']=$v['status'];
		$info['total_price']=$v['total_price'];
		$info['discount_price']=$v['discount_price'];
		$info['remark']=$v['remark'];
		$info['mobile']=$v['mobile'];
		$info['address']=$v['address'];
		$info['receive_user']=$v['receive_user'];
		$info['means_of_payment'] = $v['means_of_payment'];
		if($v['pay_time'])
		$info['pay_type']=$this->get_pay_type($v['means_of_payment'],$v['type']);	

		if($v['is_del']==1){
			$info['order_status']='已取消';	
		}else{
			$info['order_status']=$this->get_order_status($info['status']);	
		}	

		$this->assign('title','诚车堂-订货管理小助手！');
		$this->assign('goods_list',$goods_list);
		$this->assign('info',$info);
		$this->display();
	}


	public function actionmerge_order_detail(){
		$id=intval($_REQUEST['id']);
		$account_info=session('account_info');
		$location_id=$account_info['location_ids'][0];

		
		$info=M('pms_merge_order')->where('id='.$id.' and location_id='.$location_id.' and pay_time=0 and system=0 ')->find();
		if(!$info){
			$this->error('无此订单信息',U('Biz/purchase_order'),3);
		}
		$goods=M('pms_order_item as poi')->field('poi.goods_name,poi.goods_id,poi.sell_price,poi.num,poi.thumbnail,pg.supplier_id,pg.unit,fps.name,fps.mobile,fpo.*')->join('fw_pms_goods as pg on pg.id=poi.goods_id')->join('fw_pms_supplier as fps on fps.id=pg.supplier_id')->join('fw_pms_order as fpo on fpo.id=poi.order_id')->where(' fpo.system=0 and poi.order_id in('.$info['order_ids'].')')->select();
		//var_dump($goods);exit;
		foreach ($goods as $k => $v) {

			$goods_list[$v['supplier_id']]['supplier_name']=$v['name'];
			$goods_list[$v['supplier_id']]['mobile']=$v['mobile'];
			$goods_list[$v['supplier_id']]['total_price']=$v['total_price'];
			$goods_list[$v['supplier_id']]['discount_price']=$v['discount_price'];
			$goods_list[$v['supplier_id']]['remark']=$v['remark'];
			$goods_list[$v['supplier_id']]['goods'][]=$v;
		}
		
		$info['address']=$v['address'];
		$info['receive_user']=$v['receive_user'];		

		$this->assign('title','诚车堂-订货管理小助手！');
		$this->assign('goods_list',$goods_list);
		$this->assign('info',$info);
		$this->display();
	}

	/**
	* 采购支付方式
	**/
	private function get_pay_type($means_of_payment,$type){
		if($type == 1){
			$pay_type = '(现金挂账结算)';
		}elseif ($type == 2) {
			$pay_type = '(月底挂账结算)';
		}elseif ($type == 3) {
			$pay_type = '(约定挂账结算)';
		}
		switch ($means_of_payment) {
			case 1:
				$msg = '支付宝';
				break;
			case 2:
				$msg = '微信';
				break;
			case 3:
				$msg = '现金支付'.$pay_type;
				break;
			case 4:
				$msg = '刷卡支付'.$pay_type;
				break;
			case 5:
				$msg = '转账支付'.$pay_type;
				break;
			case 6:
				$msg = '物流代收'.$pay_type;
				break;		
			default:
				$msg = '-';
				break;
		}

		return $msg;
	}

	/**
	* 获得订单状态
	**/
	private function get_order_status($status_num){

		switch ($status_num) {
			case 1:
				$status = '未发货';
				break;
			case 2:
				$status = '已发货';
				break;
			case 3:
				$status = '未收货';
				break;
			case 4:
				$status = '已收货';
				break;
			case 5:
				$status = '已作废';
				break;	
			default:
				$status = '';
				break;
		}

		return $status;
	}

	//微信授权、用于采购系统推送消息
	public function actionauthorize(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}

		$account_info=session('account_info');
		if($account_info['allow_delivery']=='1'){
			$this->error('请用单店账号登录',U('Biz/shop_count'),3);
		}
		$location_id = $account_info['location_ids'][0];

		$open_id=session('oprate_opend_id');
		if(!$open_id){
			$refererUrl = U('Biz/authorize'); //登录前一个页面的Url		
			$redirectUrl = urlencode(DOMAIN_URL.U('Agent/OAuth_wx'));  //授权后重定向的回调链接地址，请使用urlencode对链接进行处理 
			$Url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxb09359ac1d3f2267&redirect_uri=".$redirectUrl."&response_type=code&scope=snsapi_base&state=".$refererUrl."#wechat_redirect";
			header("Location:".$Url);
		}

		$info = M('pms_weixin')->where(array('relation_id'=>$location_id,'open_id'=>$open_id,'type'=>1))->find();

		$this->assign('info',$info);
		$this->display();
	}
	
	//授权
	public function actionauthorize_add(){
		if(!session('account_info')){
			$this->ajaxReturn(U('Biz/login'),'请重新登录',0);
		}
		$account_info=session('account_info');
		if($account_info['allow_delivery']=='1'){
			$this->ajaxReturn(U('Biz/shop_count'),'请用单店账号登录',0);
		}
		$location_id = $account_info['location_ids'][0];

		$open_id=session('oprate_opend_id');

		if(!$open_id){
			$this->ajaxReturn(U('Biz/authorize'),'请重新授权',0);
		}

		$info = M('pms_weixin')->where(array('relation_id'=>$location_id,'open_id'=>$open_id,'type'=>1))->find();

		if($info){
			$this->ajaxReturn(U('Biz/authorize'),'您已授权，无需重复授权',0);
		}

		$data['account_id'] = $account_info['id'];
		$data['account_name'] = $account_info['account_name'];
		$data['open_id'] = $open_id;
		$data['relation_id'] = $location_id;
		$data['type'] = 1;
		$data['create_time'] = time();

		$result = M('pms_weixin')->add($data);

		if($result){
			$this->ajaxReturn(U('Biz/authorize'),'授权成功',1);
		}else{
			$this->ajaxReturn(U('Biz/authorize'),'授权失败，请重新授权',0);
		}
	}

	//删除授权
	public function actionauthorize_del(){
		if(!session('account_info')){
			$this->ajaxReturn(U('Biz/login'),'请重新登录',0);
		}
		$account_info=session('account_info');
		if($account_info['allow_delivery']=='1'){
			$this->ajaxReturn(U('Biz/shop_count'),'请用单店账号登录',0);
		}
		$location_id = $account_info['location_ids'][0];

		$open_id=session('oprate_opend_id');

		if(!$open_id){
			$this->ajaxReturn(U('Biz/authorize'),'信息不存在',0);
		}

		$info = M('pms_weixin')->where(array('relation_id'=>$location_id,'open_id'=>$open_id,'type'=>1))->find();

		if(!$info){
			$this->ajaxReturn(U('Biz/authorize'),'信息不存在',0);
		}

		$result = M('pms_weixin')->where(array('relation_id'=>$location_id,'open_id'=>$open_id,'type'=>1))->delete(); 

		if($result){
			$this->ajaxReturn(U('Biz/authorize'),'取消授权成功',1);
		}else{
			$this->ajaxReturn(U('Biz/authorize'),'取消授权失败',0);
		}
	}

	//门店查看预约列表
	public function actionreservation_list(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$this->assign('title','预约列表');
		$this->display();
	}

	//ajax加载预约信息
	public function actionajax_get_reservation(){
		
		$account_info=session('account_info');
		if($account_info['allow_delivery']=='1'){
			$this->ajaxReturn(U('Biz/shop_count'),'请用单店账号登录',0);
		}
		$location_id = $account_info['location_ids'][0];

		$page = intval($_REQUEST['p']);
		$limit =($page*8).",8";
		
		$condition = trim($_REQUEST['qt']);
		$condition = !empty($condition) ? $condition : "全部";

		switch ($condition) {
			case '全部':
				$sql = "SELECT r.id,r.status,r.service_time,r.item,r.store_name,r.store_contact,r.store_address,r.car_owner,r.user_mobile,r.remark FROM fw_reservation r WHERE r.store_id=".$location_id." ORDER BY r.id DESC LIMIT ".$limit;
				break;

			case '本周':
				$maxtime = time();
				$mintime = strtotime("last monday");
				$sql = "SELECT r.id,r.status,r.service_time,r.item,r.store_name,r.store_contact,r.store_address,r.car_owner,r.user_mobile,r.remark FROM fw_reservation r WHERE r.store_id=".$location_id." AND create_time > {$mintime} AND create_time < {$maxtime} ORDER BY r.id DESC LIMIT ".$limit;
				break;

			case '本月':

				$y = date('Y');
				$m = date('m');

				$maxtime = time();
				$mintime = strtotime($y.'-'.$m.'-'.'01 00:00:00');
				// dump($mintime);
				$sql = "SELECT r.id,r.status,r.service_time,r.item,r.store_name,r.store_contact,r.store_address,r.car_owner,r.user_mobile,r.remark FROM fw_reservation r WHERE r.store_id=".$location_id." AND create_time > {$mintime} AND create_time < {$maxtime} ORDER BY r.id DESC LIMIT ".$limit;
				break;

			case '更早':

				$y = date('Y');
				$m = date('m');

				$time = strtotime($y.'-'.$m.'-'.'01 00:00:00');
				$sql = "SELECT r.id,r.status,r.service_time,r.item,r.store_name,r.store_contact,r.store_address,r.car_owner,r.user_mobile,r.remark FROM fw_reservation r WHERE r.store_id=".$location_id." AND create_time < {$time} ORDER BY r.id DESC LIMIT ".$limit;
				break;
			
			default:
				die("找不到合适的内容，请稍后重试");
				break;
		}

		$record = M('reservation')->query($sql);
		
		$this->assign('ajaxRecord',$record);
		echo $html=$this->fetch();
	}

	//我的
	public function actionmy_home(){
		if(!session('account_info')){
			header("Location:".U("Biz/login"));
		}
		$this->assign('title','我的');
		$this->display();
	}
}
?>