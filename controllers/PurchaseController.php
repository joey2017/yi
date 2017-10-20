<?php

class PurchaseAction extends Action {

	//采购主页
	public function home(){

			if(!session('account_info')){				
				session('redirect_url',U('Purchase/home'));
				header("Location:".U("Biz/login"));
			}else{
			
				// $qualitygoods=M('pms_goods')->field('id,goods_name,thumbnail,sales,price,promotion_price,unit')->where('is_del=0 and is_sale=1 and price>0 and is_top=1')->order('supplier_id asc')->limit(8)->select();

				// foreach ($qualitygoods as $k => $v) {
				// 	if($v['promotion_price']>0){
				// 		$qualitygoods[$k]['price']=$v['promotion_price'];
				// 	}
				// }
				$this->assign('title','诚车堂-订货管理小助手！');
				//$this->assign('qualitygoods',$qualitygoods);
				$this->display();
			}
		
	}

	//采购商品列表
	public function index()
	{
			if(!session('account_info')){				
				session('redirect_url',U('Purchase/index'));
				header("Location:".U("Biz/login"));
			}

			$t=intval($_REQUEST['t'])==0?2:intval($_REQUEST['t']);

			$class_list=M('pms_class')->where('is_del=0')->order('sort asc')->select();
			foreach ($class_list as $k => $v) {
				if($v['pid']==0){
					$cl[$v['id']]['c_name']=$v['class_name'];
				}else{
					$cl[$v['pid']]['item'][]=$v;
				}

				if($v['id']==$t){
					$class_name=$v['class_name'];
				}
			}

			$attr_list=M('pms_attr')->field('id,attr_name,attr_val')->where('class_id=2')->order('sort desc')->select();

			foreach ($attr_list as $k => $v) {
				$attr_list[$k]['attr_val'] = explode(',', $v['attr_val']);
			}
	
			$this->assign('title','诚车堂-订货管理小助手！');
			$this->assign('t',$t);
			$this->assign('class_name',$class_name);
			$this->assign('class_list',$cl);
			$this->assign('attr_list',$attr_list);
			$this->display();
	
	}

	//精品推荐列表
	public function ajax_get_qualitygoods(){
		$page = intval($_REQUEST['p']);
		$limit =($page*8).",8";

		$qualitygoods=M()->query("select pg.id,pg.goods_name,pg.thumbnail,pg.price,pg.unit,pg.promotion_price,ps.name as supplier_name,pg.sales from fw_pms_goods as pg left join fw_pms_goods_attr as pga on pga.goods_id=pg.id left join fw_pms_supplier as ps on ps.id=pg.supplier_id where pg.is_sale=1 and pg.is_del=0 and pg.is_top=1 order by pg.supplier_id asc limit ".$limit);
		
		foreach ($qualitygoods as $k => $v) {
			if($v['promotion_price']>0){
				$qualitygoods[$k]['price']=$v['promotion_price'];
			}
		}
	
		$this->assign('qualitygoods',$qualitygoods);
		echo $html=$this->fetch();
	}

	public function class_list(){
		$t=intval($_REQUEST['t']);
		$list=M('pms_class')->where('is_del=0')->order('sort asc')->select();
		foreach ($list as $k => $v) {
			if($v['pid']==0){
				$cl[$v['id']]['c_name']=$v['class_name'];
			}else{
				$cl[$v['pid']]['item'][]=$v;
			}
			
		}
		$this->assign('title','诚车堂-订货管理小助手！');		$this->assign('t',$t);
		$this->assign('cl',$cl);
		$this->display();
	}

	//首页搜索
	public function search(){
		if(!session('account_info')){			
			session('redirect_url',U('Purchase/search'));
			header("Location:".U("Biz/login"));
		}
		$keyword=trim($_REQUEST['keyword']);
		$this->assign('keyword',$keyword);
		$this->assign('title','诚车堂-订货管理小助手！');
		$this->display();
	}


	//获取商品
	public function ajax_get_goods(){

		$index_search = isset($_REQUEST['index_search'])?intval($_REQUEST['index_search']):0;//首页搜索条件
		$class_id = isset($_REQUEST['class_id'])?intval($_REQUEST['class_id']):2;//默认轮胎分类id
		$keyword = isset($_REQUEST['keyword'])?trim($_REQUEST['keyword']):'';
		$attr = isset($_REQUEST['attr'])?trim($_REQUEST['attr']):'';
		$sort = intval($_REQUEST['sort']);
		$price_sort = intval($_REQUEST['price_sort']);
		$page = intval($_REQUEST['p']);
		$limit =($page*8).",8";

		$where = '';		
		

		//根据关键字搜索
		if($keyword){
			$where .= " and pg.goods_name like '%".$keyword."%'";
		}

		if(!$index_search){
			$where.=" and pg.class_id=".$class_id;
		}

		//根据属性筛选
		if($attr){
			$attr = explode(',', $attr);
			foreach ($attr as $k => $v) {
				$where .= " and FIND_IN_SET('".$v."',pga.attr_val)";
			}
		}

		//按销量排序
		if(in_array($sort, array(0,1,2,3,4))){
			switch ($sort) {
				case 0:
					$sort = " order by pg.id asc";
					break;
				case 1:
					$sort = " order by pg.price asc";
					break;
				case 2:
					$sort = " order by pg.price desc";
					break;
				case 3:
					$sort = " order by pg.sales desc";
					break;
				case 4:
					$sort = " order by pg.sales asc";
					break;
			}			
		}


		$goods=M()->query("select pg.id,pg.goods_name,pg.thumbnail,pg.price,pg.unit,pg.promotion_price,ps.name as supplier_name,pg.sales from fw_pms_goods as pg left join fw_pms_goods_attr as pga on pga.goods_id=pg.id left join fw_pms_supplier as ps on ps.id=pg.supplier_id where pg.is_sale=1 and pg.is_merge=0 and pg.is_del=0 ".$where.$sort." limit ".$limit);
		
		foreach ($goods as $k => $v) {
			if($v['promotion_price']>0){
				$goods[$k]['price']=$v['promotion_price'];
			}
		}
	
		$this->assign('goods',$goods);
		echo $html=$this->fetch();		
	}

	//获取分类属性
	public function ajax_get_attr(){

		//默认轮胎id
		$class_id = isset($_REQUEST['class_id'])?intval($_REQUEST['class_id']):2;

		$attr_list=M('pms_attr')->field('id,attr_name,attr_val')->where('class_id='.$class_id)->order('sort desc')->select();

		foreach ($attr_list as $k => $v) {
			$attr_list[$k]['attr_val'] = explode(',', $v['attr_val']);		}

		$this->assign('attr_list',$attr_list);

		echo $html=$this->fetch();
	}

	//采购商品详情
	public function detail()
	{
		$id=intval($_GET['id']);
		if(!session('account_info')){
			session('redirect_url',U('Purchase/detail',array('id'=>$id)));
			header("Location:".U("Biz/login"));

		}

		

		$where=" and pg.id=".$id;

		//详情信息
		$info=M('pms_goods as pg')->join('fw_pms_goods_attr as fpga on fpga.goods_id=pg.id')->join('fw_pms_supplier as fps on fps.id=pg.supplier_id')->field('pg.id,pg.goods_name,pg.unit,pg.sales,pg.thumbnail,pg.price,pg.stock,pg.promotion_price,pg.market_price,pg.detail,pg.imgs,pg.car_ids,fpga.attr_val,fpga.attr_name_val,fps.name as supplier_name,fps.qq')->where('pg.is_sale=1 and pg.is_del=0 '.$where)->find();
		if(!$info){
			$this->error('商品不存在或已下架',U('Purchase/index'),3);
		}

		$info['imgs']=array_values(array_filter(explode(',',$info['imgs'])));


		// if($info['promotion_price']>0){
		// 	$info['price']=$info['promotion_price'];
		// }
		$info['detail']=str_replace('src="/ueditor/','src="http://www.17cct.com/ueditor/',$info['detail']);
		$attr_val=explode(',',$info['attr_name_val']);	

		foreach ($attr_val as $k => $v) {
			$value=explode('：',$v);
			if($info['class_id']==2){
				if($value[0]=='胎面宽度'){
				$last_attr=$value[1].'/';
				}
				elseif($value[0]=='扁平比')
				{
					$last_attr.=$value[1].'R';
				}
				elseif($value[0]=='轮胎直径'){
					$last_attr.=$value[1];
				}
			}elseif($info['class_id']==8){
				if($value[0]=='长度'){
					$last_attr=$value[1].'*';
				}
				elseif($value[0]=='宽度')
				{
					$last_attr.=$value[1].'*';
				}
				elseif($value[0]=='高度'){
					$last_attr.=$value[1];
				}
			}
			$attr_names[]=$value[0];
			$attr_vals[]=$value[1];
		}
		
		if($info['class_id']==2){
			array_splice($attr_names, 1,0,array('1'=>'规格'));
			array_splice($attr_vals, 1,0,array('1'=>$last_attr));
		}elseif($info['class_id']==8){
			array_splice($attr_names, 1,0,array('1'=>'尺寸'));
			array_splice($attr_vals, 1,0,array('1'=>$last_attr));
		}
		
		//适用车型
		if($info['car_ids']){
			$car_list = M('car')->field('id,name,parent_id,level')->where('level in(0,1,2) and id in('.$info['car_ids'].')')->select();
			if($car_list){
				foreach ($car_list as $k => $v) {
					if($v['level'] == 1){
						$car[$v['id']]['cate2'] = $v['name']; 
					}
					if($v['level'] == 2){
						$car[$v['parent_id']]['cate3'][] = $v['name']; 
					}
				}
				foreach ($car as $k => $v) {
					$car[$k]['cate3'] = implode(' 、', $v['cate3']);
				}
			}
		}
		$cart_info=$this->get_location_cart_info();
		$this->assign('cart_num',intval($cart_info['number']));
		$this->assign("attr_vals",$attr_vals);
		$this->assign("attr_names",$attr_names);
		$this->assign("car",$car);
		$this->assign('info',$info);
		$this->assign('title',$info['goods_name']);
		$this->display();
	}

	//加入购物车
	public function add_card(){
		$cart['goods_id']=intval($_POST['goods_id']);
		$goods_info=M('pms_goods')->field('id,goods_name,price,supplier_id,promotion_price')->where('is_del=0 and id=0'.$cart['goods_id'])->find();
	
		if(!$goods_info){
			$result['status']=0;
			$result['info']='商品不存在或已下架';
			$this->ajaxReturn($result);	
		}

		$cart['number']=intval($_POST['goods_num']);

		if($cart['number']<=0){
			$result['status']=0;
			$result['info']='购买商品至少为1件';
			$this->ajaxReturn($result);
		}
		
		$location_id=$this->get_location_ids();
		if(!$location_id){
			$result['status']=-1;
			$result['info']='请先登录后再加入购物车';
			$result['url']=U('Biz/login');
			$this->ajaxReturn($result);
		}

		//判断商品是否已添加过在购物车
		$goods_cart_info=M('pms_erp_cart')->field('id,number')->where('goods_id='.$cart['goods_id'].' and location_id='.$location_id)->find();

		if($goods_cart_info){			

			//判断商品库存
			$stock_info=$this->goods_stock_info($cart['goods_id'],$cart['number']);

			/*if($stock_info<0){
				$result['status']=0;
				$result['info']='商品库存不足';
				$this->ajaxReturn($result);
			}*/

			$update_cart['number']=intval($goods_cart_info['number'])+intval($cart['number']);
			$r=M('pms_erp_cart')->where('id='.$goods_cart_info['id'])->save($update_cart);			
		}else{

			//判断商品库存
			$stock_info=$this->goods_stock_info($cart['goods_id'],$cart['number']);

			/*if($stock_info<0){
				$result['status']=0;
				$result['info']='商品库存不足';
				$this->ajaxReturn($result);
			}*/

			$cart['goods_name']=$goods_info['goods_name'];
			$cart['price']=$goods_info['promotion_price']>0?$goods_info['promotion_price']:$goods_info['price'];
			$cart['supplier_id']=$goods_info['supplier_id'];
			$cart['location_id']=$location_id;
			$cart['create_time']=time();
			$r=M('pms_erp_cart')->add($cart);
		}		

		if($r){
			$cart_info=$this->get_location_cart_info();
			$result['status']=1;
			$result['stock']=$cart_info['number'];//购物车中的商品数量
			$result['info']='加入购物车成功';			
		}else{
			$result['status']=0;
			$result['info']='加入购物车失败';
		}
		$this->ajaxReturn($result);
	}

	//购物车管理
	public function cart(){

		$location_id=$this->get_location_ids();

		$info=M()->query("select pg.thumbnail,pg.stock,pec.*,ps.name,pga.attr_name_val from fw_pms_erp_cart as pec left join fw_pms_goods as pg on pg.id=pec.goods_id left join fw_pms_supplier as ps on ps.id=pg.supplier_id left join fw_pms_goods_attr as pga on pga.goods_id=pg.id  where  pec.location_id=".intval($location_id));
		

		foreach ($info as $k => $v) {
			$v['total_price']=$v['price']*$v['number'];
			$total['price']+=$v['total_price'];
			$total['count']+=$v['number'];
			$v['attr_name']=explode(',',$v['attr_name_val']);
			$v['change_stock']=intval($v['stock']);//操作库存
			$v['stock']=$this->goods_stock_info($v['goods_id'],0);//显示库存			
			$cart_info[$v['name']]['item'][]=$v;
			$cart_info[$v['name']]['supplier_id']=$v['supplier_id'];
		}

		//商品活动输出
		// $total['price']=round($total['price'],2);
		// $total['count']=intval($total['count']);
		$this->assign('total',$total);
		$this->assign('cart_info',$cart_info);
		$this->assign('title','诚车堂-订货管理小助手！');
		$this->display();
	}


	//修改购物车
	public function modify_cart(){
		
		$id=intval($_POST['id']);//修改记录id
		$location_id=intval($this->get_location_ids());
		$update_cart['number']=intval($_POST['number']);//修改后的数量
		$type=$_POST['type'];
		if($update_cart['number']<=0){
			$result['status']=0;
			$result['info']='购买商品至少为1件';
			ajax_return($result);
		}

		$cart_info=M('pms_erp_cart')->where('id='.$id.' and location_id='.$location_id)->find();

		if($cart_info){

			/*if($type=='a'){
				//判断商品库存
				$stock_info=$this->goods_stock_info($cart_info['goods_id'],1);

				if($stock_info<0){
					$result['status']=0;
					$result['info']='商品库存不足';
					$this->ajaxReturn($result);					
				}
			}*/

			$r=M('pms_erp_cart')->where('id='.$id)->save($update_cart);

			if($r){
				$location_cart_info=$this->get_location_cart_info();
				$result['number']=$location_cart_info['number'];
				$result['total_price']=round($location_cart_info['total_price'],2);
				$result['status']=1;
				$result['info']='修改成功';			
			}else{
				$result['status']=0;
				$result['info']='修改失败';				
			}
			$this->ajaxReturn($result);				

		}else{
			$result['status']=0;
			$result['info']='修改失败';
			$this->ajaxReturn($result);	
		}
	}

	//删除购物车
	public function delete_cart(){
		$id=intval($_POST['id']);

		if(!$id){
			$result['status']=0;
			$result['info']='请选择要删除的商品';	
			$this->ajaxReturn($result);	
		}

		$location_id=intval($this->get_location_ids());

		$r=M('pms_erp_cart')->where("id=".$id." and location_id=".$location_id)->delete();
	
		if($r){
			$cart_info=$this->get_location_cart_info();
			$result['number']=intval($cart_info['number']);
			$result['total_price']=round($cart_info['total_price'],2);
			$result['status']=1;
			$result['info']=$info.'成功';		
		}else{
			$result['status']=0;
			$result['info']=$info.'失败';		
		}

	   $this->ajaxReturn($result);		  
	}

	//检查订单
	public function check_order(){

		if(!session('account_info')){
			session('redirect_url',U('Purchase/check_order'));
			header("Location:".U("Biz/login"));
		}

		$ids=substr($_GET['ids'],0,-1);

		$location_id = $this->get_location_ids();


		$cart_info = M('pms_erp_cart')->where('location_id='.$location_id." and id in(".$ids.")")->getField('id');		
		
		
		if(!$cart_info){
			$this->error('购物车还没有商品',U('Purchase/index'),3);
		}

		session('cart_ids',$_GET['ids']);

		//地址
		$address = M('pms_address')->where('is_default=1 and location_id='.$location_id)->find();		
			
		//购物车商品信息
		$goods_info =M()->query("select pg.thumbnail,pg.price as goods_price,pg.promotion_price,pg.stock,pg.unit,pec.*,ps.name,pga.attr_name_val from fw_pms_erp_cart as pec left join fw_pms_goods as pg on pg.id=pec.goods_id left join fw_pms_supplier as ps on ps.id=pg.supplier_id left join fw_pms_goods_attr as pga on pga.goods_id=pg.id  where  pec.location_id=".intval($location_id)." and pec.id in(".$ids.")");
		
		foreach ($goods_info as $k => $v) {
			//使用最新价格，如果有促销价则使用促销价
            $v['price'] = $v['promotion_price']>0 ? $v['promotion_price'] : $v['goods_price'];
            $p[$v['supplier_id']]['total_price'] += $v['price']*$v['number'];
            $v['total_price'] = $p[$v['supplier_id']]['total_price'];
            $v['attr_name'] = explode(',',$v['attr_name_val']);
            $goods[$v['supplier_id']][] = $v;
            $total['price'] += $v['price']*$v['number'];
            $total['count'] += $v['number'];
            $datas[$v['supplier_id']]['goods_id'][] = $v['goods_id'];
            $datas[$v['supplier_id']]['price'][] += $v['total_price'];
        }
		
		foreach ($datas as $k => $v) {
			//供应商活动列表
			$goods[$k][0]['activity'] = $this->get_activity($v['goods_id'],$v['price'],$k);
			//能使用的优惠券列表
			$goods[$k][0]['coupon'] = $this->get_coupon($v['goods_id'],$v['price'],$k);
		}
		
		$this->assign('title','诚车堂-订货管理小助手！');
		$this->assign('address',$address);
		$this->assign('province_list',$province_list);
		$this->assign('goods',$goods);
		$this->assign('total',$total);
		$this->display();	

		
	}


	//创建订单
	public function create_order(){

		$location_id=$this->get_location_ids();

		$ids=substr(session('cart_ids'),0,-1);

		$cart_info=M()->query("select pg.thumbnail,pg.price as goods_price,pg.promotion_price,pg.stock,pec.*,ps.name,pga.attr_name_val from fw_pms_erp_cart as pec left join fw_pms_goods as pg on pg.id=pec.goods_id left join fw_pms_supplier as ps on ps.id=pg.supplier_id left join fw_pms_goods_attr as pga on pga.goods_id=pg.id  where  pec.location_id=".intval($location_id)." and pec.id in(".$ids.")"); 
		
		if(!$cart_info){
			$result['status']=0;
			$result['info']='购物车空空如也,先去采购吧';
			$this->ajaxReturn($result);	
		}		

		$account_info = session('account_info');
		//$location_info = $this->get_location_info();

		$address_id = intval($_POST['address_id']);
		$address = M('pms_address')->where('id='.$address_id.' and location_id='.$location_id)->find();

		foreach ($cart_info as $k => $v) {
			
			//使用最新价格，如果有促销价则使用促销价
			$v['price'] = $v['promotion_price']>0 ? $v['promotion_price'] : $v['goods_price'];
			$v['total_price'] = $v['price']*$v['number'];//小计
			$create_order_price +=$v['total_price'];
			$order_info[$v['supplier_id']][] = $v;
			$datas[$v['supplier_id']]['goods_id'][] = $v['goods_id'];
			$datas[$v['supplier_id']]['price'][] = $v['total_price'];
		}

		if($create_order_price<=0){
			$result['status']=0;
			$result['info']='订单总价不能为0';
			$this->ajaxReturn($result);	
		}

		// 活动优惠或折扣id
		$act_rule_id = trim($_POST['act_rule_id']);//规则id列表,一个店铺一个活动
		// 门店优惠券id
		$coupon_ids = trim($_POST['coupon_ids']);//门店优惠券id列表,一个店铺使用一张

		//判断是否能参与活动、使用优惠券。并返回一个二维数组
		$act_data = $this->use_act_coupon($act_rule_id,$coupon_ids,$datas);
		
		//供应商id数组和留言数组
		$supplier_id = $_POST['supplier_id'];
		$remark = $_POST['remark'];

		foreach ($supplier_id as $k => $v) {
			$new_remark[$v] = $remark[$k];
		}

		//生成订单 多个店铺生成多个订单
		foreach ($order_info as $k => $v) {
			$order['order_sn'] = 'MD'.date('Ymdhis',time()).rand(10,99);
			$order['purchase_user_id'] = $account_info['id'];
			$order['location_id'] = $location_id;
			$order['location_name'] = M('supplier_location')->where('id='.$location_id)->getField('name');
			$order['receive_user'] = $address['name'];
			$order['receive_tel'] = $address['tel'];
			$order['create_time'] = time();
			$order['supplier_id'] = $k;
			$order['pay_status'] = 0;
			$order['status'] = 1;
			$order['total_original_price'] = $act_data[$k]['total_original_price'];
			$order['total_price'] = $act_data[$k]['total_price'];
			$order['act_id'] = $act_data[$k]['act_id'];
			$order['location_coupon_id'] = $act_data[$k]['location_coupon_id'];
			$order['discount_price'] = $act_data[$k]['discount_price'];
			$order['is_del'] = 0;
			$order['address'] = $address['full_address'];
			$order['remark'] = $new_remark[$k];
			
			$order_id=M('pms_order')->add($order);
			

			if($order_id){

				//将单个或多个order_id写入合并订单
				$all_order_id[] = $order_id;
				//合并订单金额
				$all_total_price += $act_data[$k]['total_price'];
				//初始化
				$order_costs = $order_nums = 0;

				//写入订单详情			
				foreach ($v as $i_k => $i_v) {
					$order_costs += $i_v['costs'] * $i_v['number'];
					$order_nums += $i_v['number'];
					$item_data['order_id']=$order_id;
					$item_data['goods_name']=$i_v['goods_name'];
					$item_data['goods_id']=$i_v['goods_id'];
					$item_data['sell_price']=$i_v['price'];
					$item_data['num']=$i_v['number'];
					$item_data['thumbnail']=$i_v['thumbnail'];
					$item_data['attr_val']=$i_v['attr_name_val'];					
    				$item_result = M('pms_order_item')->add($item_data);
                }
				
				$update_sql="UPDATE fw_pms_order SET `costs`=".$order_costs.",total_num=".$order_nums." WHERE `id`=".$order_id;
				M()->query($update_sql);				

				if(!$item_result){

					$result['status']=0;
					$result['info']='订单详情创建失败';
					$this->ajaxReturn($result);	
				}

			}else{
				$result['status']=0;
				$result['info']='订单详情创建失败';
				$this->ajaxReturn($result);	
			}
			
		}
		//创建合并订单
		if($all_order_id && $all_total_price){
			
			$merge_order['order_sn'] = 'JY'.date('Ymdhis',time()).rand(10,99);
			$merge_order['order_ids'] = implode(',', $all_order_id);
			$merge_order['receive_user'] = $address['name'];
			$merge_order['receive_tel'] = $address['tel'];
			$merge_order['location_id'] = $location_id;
			$merge_order['create_time'] = time();
			$merge_order['total_price'] = $all_total_price;
			$merge_order['pay_status'] = 0;
			$merge_order['pay_time'] = 0;
			$merge_order['means_of_payment'] = 0;
			$merge_order['is_del'] = 0;

			$merge_order_id=M('pms_merge_order')->add($merge_order);
			

			if($merge_order_id){
				//订单创建成功后清空购物车
				M('pms_erp_cart')->where('location_id='.$location_id)->delete();
				$result['status']=1;
				$result['info']='订单创建成功';
				$result['order_id']=$merge_order_id;
				$this->ajaxReturn($result);	
			}else{
				$result['status']=0;
				$result['info']='订单创建失败';
				$this->ajaxReturn($result);
			}

		}else{
			$result['status']=0;
			$result['info']='订单创建失败';
			$this->ajaxReturn($result);
		}

	}

	//订单信息
	public function order(){
		$id=intval($_REQUEST['id']);
		$t=trim($_REQUEST['t']);
		$location_id=$this->get_location_ids();

		if($t=='pms_merge_order'){
			$order_info=M('pms_merge_order')->where('id='.$id.' and pay_status=0 and system=0 and location_id='.$location_id)->find();			
		}else{
			$order_info=M('pms_order')->where('id='.$id.' and purchase_user_id=0 and system=0 and location_id='.$location_id)->find();

			$order_info['pay_type']=$this->get_pay_type($order_info['means_of_payment'],$order_info['pay_type']);		
		}

		if(!$order_info){
			$this->error('无此订单信息',U('Purchase/index'),3);
		}
		
		$this->assign('oi',$order_info);
		$this->assign('title','诚车堂-订货管理小助手！');
		$this->display();
	}

	//确认订单
	public function confirm_order(){

		$id = intval($_REQUEST['id']);
		$location_id=intval($this->get_location_ids());
		$account_info=session('account_info');

		$data['purchase_user_id']=$account_info['id'];
		$r=M('pms_order')->where('id='.$id.' and is_del=0 and location_id='.$location_id)->save($data);
		if($r){
			$result['status']=1;
			$result['msg']='订单确认成功';
		}else{
			$result['status']=0;
			$result['msg']='订单确认失败';
		}
		$this->ajaxReturn($result);
	}

	//收货
	public function receipt_goods(){
		$id=intval($_REQUEST['id']);
		$t=trim($_REQUEST['t']);
		$location_id=$this->get_location_ids();
	
		$order_info=M('pms_order as po')->join('fw_pms_supplier as ps on ps.id=po.supplier_id')->field('po.*,ps.name as supplier_name,ps.mobile as supplier_mobile')->where('po.id='.$id.' and po.system=0 and po.location_id='.$location_id)->find();
		//var_dump(M('pms_order as po')->getlastsql());
		$order_info['pay_type']=$this->get_pay_type($order_info['means_of_payment'],$order_info['pay_type']);				
		$order_info['order_status']=$this->get_order_status($order_info['status']);
		if(!$order_info){
			$this->error('无此订单信息',U('Purchase/index'),3);
		}
		
		$this->assign('oi',$order_info);
		$this->display();
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


	//确认收货
	public function confirm_receipt(){

		$id = intval($_REQUEST['id']);
		$location_id=intval($this->get_location_ids());
		$account_info=session('account_info');

		$data['status']=4;
		$r=M('pms_order')->where('id='.$id.' and is_del=0 and location_id='.$location_id)->save($data);
		if($r){
			$result['status']=1;
			$result['msg']='确认收货成功';

			//给供应商发送微信消息
			$order_info = M('pms_order')->field('order_sn,location_name,supplier_id,type')->where('id='.$id.' and is_del=0 and system=0 and location_id='.$location_id)->find();
			if($order_info){

				$openid_list=M('pms_weixin')->field('open_id')->where('relation_id='.$order_info['supplier_id'].' and type=2')->select();
				if($openid_list){
					foreach ($openid_list as $k => $v) {
						$this->send_supplier_wx_msg($v['open_id'],$order_info['order_sn'],$order_info['location_name'],2);
					}
				}
			}

		}else{
			$result['status']=0;
			$result['msg']='确认收货失败';
		}
		$this->ajaxReturn($result);

	}

	/**
	* 给供应商发送微信消息
	**/
	private function send_supplier_wx_msg($wxid,$order_sn,$location_name,$type,$name=''){

		if ($type == 1) {
			$first = '"'.$location_name.'"已确认订单，请按流程快速操作！';
			$status = "已确认";
		}elseif ($type == 2) {
			$first = '"'.$location_name.'"已确认收货，请知悉！';
			$status = "已收货";
		}else{
			$first = '"'.$name.'"，您好！"'.$location_name.'"给你下了一条新的订单，请及时处理！打印订单前先致电客户了解具体订单和收款情况！';
			$status = '新订单';
		}
		
		$json=array("touser"=>$wxid,
					"template_id"=>"P4l0GDvpYTR_DmSKflGmYehFC-w8VKxMCRTk8ktvE7M",
					"topcolor"=>"#FF0000",
					"data"=>array('first'=>array('value'=>$first),
								'keyword1'=>array('value'=>$order_sn),
								'keyword2'=>array('value'=>$status),
								'remark'=>array('value'=>'【车堂盛世】诚车堂-订货管理小助手！')
						)
			);

		$this->send_template_info($json);

	}

	//发送模板
	private function send_template_info($json){
		
		    $access_token  = $this->get_sj_acc_token();
			$get_token_url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;			
			$ch  = curl_init() ;
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS,urldecode(json_encode(($json))));
			curl_setopt($ch, CURLOPT_URL,$get_token_url);			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
			$result = curl_exec($ch) ;
			curl_close($ch);
	}

	/**
	* 获取诚车堂商户版access_token
	**/
	private function get_sj_acc_token()
	{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt ($ch, CURLOPT_URL, "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxb09359ac1d3f2267&secret=7e161c7930c9de1f3213dd13d6bb7a9c");
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$access_token = curl_exec($ch);
		$access_token=json_decode($access_token, true); 
		return $access_token['access_token'];	
	}

	//线下支付
	public function offline_pay(){

		$id = intval($_REQUEST['id']);
		$pay_mode = intval($_POST['pay_mode']);
		$pay_type = intval($_POST['pay_type']);
		$pay_remark = trim($_POST['pay_remark']);
			
			
		if(!in_array($pay_mode, array(3,4,5,6)))
		{			
			$result['status']=0;
			$result['msg']='支付方式不正确';
			$this->ajaxReturn($result);
		}
		$location_id=intval($this->get_location_ids());
		$order_info = M('pms_merge_order')->where('id='.$id.' and pay_status=0 and is_del=0 and system=0 and pay_time=0 and location_id='.$location_id)->find();	
		
		if($order_info && $order_info['order_ids'] && $order_info['total_price']>0){
			
			//更新合并订单状态
			$merge_update['outer_notice_sn']='线下支付';
			$merge_update['pay_time']=time();
			$merge_update['means_of_payment']=$pay_mode;
			$merge_update['pay_status']=2;
			$merge_update['pay_type']=$pay_type;
			$order_update['pay_remark']=$pay_remark;
			$r=M('pms_merge_order')->where('id='.$order_info['id'])->save($merge_update);

			//更新子订单状态
			$order_update['outer_notice_sn']='线下支付';
			$order_update['pay_time']=time();
			$order_update['means_of_payment']=$pay_mode;
			$order_update['status']=1;
			$order_update['pay_status']=1;
			$order_update['pay_type']=$pay_type;
			$order_update['pay_remark']=$pay_remark;
			$r2 =M('pms_order')->where('id in('.$order_info['order_ids'].')')->save($order_update);

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
								$location_coupon['location_id'] = intval($this->get_location_ids());
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

			if($r && $r2){
				switch ($pay_mode) {
					case 3:
						$msg = '现金支付';
						break;
					case 4:
						$msg = '刷卡支付';
						break;
					case 5:
						$msg = '转账支付';
						break;
					case 6:
						$msg = '月结支付';
						break;
					default:
						$msg = '挂账支付';
						break;
				}
				$result['status']=1;
				$result['msg']=$msg.'成功';
			}

		}else{
			$result['status']=0;
			$result['msg']='无此订单信息';
			
		}
		$this->ajaxReturn($result);
	}

	//支付回调页面
	public function pay_back()
	{
		$id = intval($_REQUEST['id']);
		$type=trim($_REQUEST['type']);
		$location_id=$this->get_location_ids();
		if($type == 'confirm'){
			$order=M('pms_order')->where('id='.$id.' and is_del=0 and system=0 and location_id='.$location_id)->find();

			//确认订单发送微信消息
			$openid_list=M('pms_weixin')->field('open_id')->where('relation_id='.$order['supplier_id'].' and type=2')->select();
			if($openid_list){
				foreach ($openid_list as $k => $v) {
					$this->send_supplier_wx_msg($v['open_id'],$order['order_sn'],$order['location_name'],1);
				}
			}

		}else{
			$order=M('pms_merge_order')->where('id='.$id.' and location_id='.$location_id.' and pay_status=2 and system=0 ')->find();

			//给供应商发送门店下单微信消息
			$order_list =M('pms_order as po')->field('po.*,ps.name')->where('po.system=0 and po.id in('.$order['order_ids'].')')->join('fw_pms_supplier as ps ON ps.id=po.supplier_id')->select();

			$openid_list = array();
			foreach ($order_list as $key => $value) {
				
				$openid_list[]=M('pms_weixin')->field('open_id')->where('relation_id='.$value['supplier_id'].' and type=2')->select();
			}
			if($openid_list){
				foreach ($openid_list as $k => $v) {
					foreach ($v as $kk => $vv) {
						$this->send_supplier_wx_msg($vv['open_id'],$order_list[$k]['order_sn'],$order_list[$k]['location_name'],3,$order_list[$k]['name']);
					}
				}
			}

		}

		if (!$order) {
			$this->error('无此订单信息',U('Purchase/home'),3);
		}

		$order['pay_type']=$this->get_pay_type($order['means_of_payment'],$order['pay_type']);
		$this->assign('order',$order);
		// $this->assign("price",$order['total_price']);
		// $this->assign("order_sn",$order['order_sn']);
		$this->assign("title","支付结果");
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


	//地址列表
	public function address_list(){

		$location_id = $this->get_location_ids();

		//收货地址
		$address_list =M('pms_address')->where('location_id='.$location_id)->select();

		$this->assign('ids',session('cart_ids'));
		$this->assign('address_list',$address_list);

		$this->display();
	}

	//地址添加
	public function address_add(){
		$r=intval($_GET['r']);
		
		$province_list =M('delivery_region')->where('region_level=2')->select();	

		if($r){
			$this->assign('ids',session('cart_ids'));
		}
		$this->assign('r',$r);	
		$this->assign('province_list',$province_list);
		$this->display();
	}

	//ajax添加地址
	public function ajax_address_add(){

		if($_POST['user_name'] && $_POST['province'] && $_POST['city'] && $_POST['area'] && $_POST['address'] && $_POST['tel']){

			$location_id = $this->get_location_ids();			
			$province = explode('_', trim($_POST['province']));
			$city = explode('_', trim($_POST['city']));
			$area = explode('_', trim($_POST['area']));

			$address['name'] = trim($_POST['user_name']);
			$address['province_id'] = $province[0];
			$address['city_id'] = $city[0];
			$address['area_id'] = $area[0];
			$address['detail_address'] = trim($_POST['address']);
			$address['full_address'] = $province[1].$city[1].$area[1].trim($_POST['address']);
			$address['tel'] = trim($_POST['tel']);
			$address['location_id'] = $location_id;
			$address['is_default'] = intval($_POST['is_default']);

			$address_id=M('pms_address')->add($address);			

			if($address_id){

				//如果新增的为默认地址，则其它地址修改为非默认
				if($address['is_default'] == 1){
					$update_data['is_default']=0;
					M('pms_address')->where('location_id='.$location_id.' and id!='.$address_id)->save($update_data);					
				}

				$this->ajaxReturn(array('status'=>1,'msg'=>'新增地址成功'));
			}else{
				$this->ajaxReturn(array('status'=>0,'msg'=>'新增地址失败'));
			}	

		}else{
			$this->ajaxReturn(array('status'=>0,'msg'=>'新增地址失败'));
		}
		
	}

	//ajax修改地址
	public function address_edit(){

		$id = intval($_GET['id']);
		
		$location_id=$this->get_location_ids();

		$address_info =M('pms_address')->where('location_id='.$location_id.' and id='.$id)->find();
		

		if(!$address_info){
			$this->error('无此地址',U('Purchase/address_list'),3);
		}

		//省份列表
		$province_list =M('delivery_region')->where('region_level=2')->select();

		//城市列表
		$city_list =M('delivery_region')->where('pid='.$address_info['province_id'])->select();

		//地区列表
		$area_list =M('delivery_region')->where('pid='.$address_info['city_id'])->select();

	
		foreach ($city_list as $k => $v) {
			if($v['id'] == $address_info['city_id']){
				$c_select = 'selected="true"';
			}else{
				$c_select = '';
			}
			$city_str .= '<option value="'.$v['id'].'_'.$v['name'].'" '.$c_select.'>'.$v['name'].'</option>';
		}
		foreach ($area_list as $k => $v) {
			if($v['id'] == $address_info['area_id']){
				$a_select = 'selected="true"';
			}else{
				$a_select = '';
			}
			$area_str .= '<option value="'.$v['id'].'_'.$v['name'].'" '.$a_select.'>'.$v['name'].'</option>';
		}

		$this->assign('province_list',$province_list);
		$this->assign('address',$address_info);
		$this->assign('city',$city_str);
		$this->assign('area',$area_str);

		$this->display();

	}

	//ajax修改地址保存
	public function ajax_address_save(){

		if($_POST['id'] && $_POST['user_name'] && $_POST['province'] && $_POST['city'] && $_POST['area'] && $_POST['address'] && $_POST['tel']){

			$id = intval($_POST['id']);
			$location_id = $this->get_location_ids();			
			$province = explode('_', trim($_POST['province']));
			$city = explode('_', trim($_POST['city']));
			$area = explode('_', trim($_POST['area']));

			$address['name'] = trim($_POST['user_name']);
			$address['province_id'] = $province[0];
			$address['city_id'] = $city[0];
			$address['area_id'] = $area[0];
			$address['detail_address'] = trim($_POST['address']);
			$address['full_address'] = $province[1].$city[1].$area[1].trim($_POST['address']);
			$address['tel'] = trim($_POST['tel']);
			$address['is_default'] = intval($_POST['is_default']);
			
			$result=M('pms_address')->where('id='.$id.' and location_id='.$location_id)->save($address);			
			if($result){
				//如果修改为默认地址，则其它地址修改为非默认
				if($address['is_default'] == 1){
					$update_data['is_default']=0;
					M('pms_address')->where('location_id='.$location_id.' and id!='.$id)->save($update_data);					
				}
				$this->ajaxReturn(array('status'=>1,'msg'=>'修改地址成功'));
			}else{
				$this->ajaxReturn(array('status'=>0,'msg'=>'修改地址失败'));
			}	

		}else{
			$this->ajaxReturn(array('status'=>0,'msg'=>'修改地址失败'));
		}	

	}

	//ajax删除地址
	public function ajax_address_del(){

		$id = intval($_POST['id']);

		if(!$id){
			$this->ajaxReturn(array('status'=>0,'msg'=>'删除失败'));
		}

		$location_id=$this->get_location_ids();
		$result =M('pms_address')->where('location_id='.$location_id.' and id='.$id)->delete();

		if($result){
			$this->ajaxReturn(array('status'=>1,'msg'=>'删除成功'));
		}else{
			$this->ajaxReturn(array('status'=>0,'msg'=>'删除失败'));
		}
		
	}

	//ajax设置默认地址
	public function ajax_set_default(){

		$id = intval($_POST['id']);
		

		if(!$id){
			$this->ajaxReturn(array('status'=>0,'msg'=>'设置失败'));
		}

		$location_id = $this->get_location_ids();

		//修改为默认地址
		$update_data['is_default']=1;
		$r1 = M('pms_address')->where('location_id='.$location_id.' and id='.$id)->save($update_data);

		//其它地址修改为非默认地址
		$update_data['is_default']=0;
		$r2 =M('pms_address')->where('location_id='.$location_id.' and id!='.$id)->save($update_data);

		if($r1 && $r2){
			$this->ajaxReturn(array('status'=>1,'msg'=>'设置成功'));
		}else{
			$this->ajaxReturn(array('status'=>0,'msg'=>'设置失败'));
		}

	}

	//根据pid获得地区
	public function get_area(){
		$id = intval($_POST['id']);
		$type = intval($_POST['type']);

		if($id){
			$area_list =M('delivery_region')->field('id,name')->where('pid='.$id)->select();
		}
		
		if($type == 1){
			$str = '<option value="0">请选择城市</option>';
		}else{
			$str = '<option value="0">请选择地区</option>';
		}
		
		if($area_list){
			foreach ($area_list as $k => $v) {
				$str .= '<option value="'.$v['id'].'_'.$v['name'].'">'.$v['name'].'</option>';
			}
		}

		$this->ajaxReturn($str);
		
	}


		/**
	* 获得能使用的优惠券列表
	* @param 	$goods_id 		 购买的商品id数组(按供应商分)
	* @param 	$price 			 购买的商品售价数组(按供应商分)
	* @param 	$supplier_id 	 供应商id
	* @return 	$location_coupon 能使用的优惠券列表
	*/
	private function get_coupon($goods_id,$price,$supplier_id){

		//查找本门店在该供应商的优惠券列表
		$location_coupon =M()->query("select id,goods_ids,full_money,discount_money from fw_pms_location_coupon where location_id=".intval($this->get_location_ids())." and supplier_id=".$supplier_id." and num>0 and (unix_timestamp() between start_time and end_time)"); 

		if($location_coupon){
			foreach ($location_coupon as $k => $v) {

				foreach ($goods_id as $g_k => $g_v) {
					if($v['goods_ids'] == 0){//所有商品
						$coupon[$v['id']]['price'] += $price[$g_k];
					}else{//部分商品
						$had_goods_id = explode(',', $v['goods_ids']);
						if(in_array($g_v, $had_goods_id)){
							$coupon[$v['id']]['price'] += $price[$g_k];
						}
					}
				}

			}

			foreach ($location_coupon as $k => $v) {

				if($coupon[$v['id']]['price'] >= $v['full_money']){//金额满足

					$is_all = $v['goods_ids'] == 0 ? '全部商品':'部分商品';
					$location_coupon[$k]['coupon_name'] = round($v['discount_money'],2)."元优惠券（".$is_all."满".round($v['full_money'],2)."元使用）";
					
				}else{
					unset($location_coupon[$k]);
				}

			}

			return $location_coupon;

		}
	}

	/**
	* 获得供应商活动
	* @param 	$goods_id 		购买的商品id数组(按供应商分)
	* @param 	$price 			购买的商品售价数组(按供应商分)
	* @param 	$supplier_id 	供应商id
	* @return 	$act_rule_list 	可使用的供应商活动规则列表
	*/
	private function get_activity($goods_id,$price,$supplier_id){
		
		//查找符合的有效期内的供应商活动
		$act_list =M()->query("select id,act_name,goods_ids,act_type from fw_pms_activity where supplier_id=".$supplier_id." and is_del=0 and (unix_timestamp() between start_time and end_time)");
		

		if($act_list){
			foreach ($act_list as $k => $v) {

				foreach ($goods_id as $g_k => $g_v) {
					if($v['goods_ids'] == 0){//所有商品
						$act[$v['id']]['price'] += $price[$g_k];
						$act[$v['id']]['act_type'] = $v['act_type'];
						$act[$v['id']]['is_all_val'] = '全部商品';
					}else{//部分商品
						$had_goods_id = explode(',', $v['goods_ids']);
						if(in_array($g_v, $had_goods_id)){
							$act[$v['id']]['price'] += $price[$g_k];
							$act[$v['id']]['act_type'] = $v['act_type'];
							$act[$v['id']]['is_all_val'] = '部分商品';
						}
					}
				}

			}

			if($act){
				foreach ($act as $k => $v) {
					$act_rule = $this->get_act_rule($k,$v['price'],$v['act_type'],$v['is_all_val']);
					if($act_rule){
						foreach ($act_rule as $a_k => $a_v) {
							$act_rule_list[] = $a_v;
						}
					}
				}
			}
			
		}

		return $act_rule_list;
			
	}

	/**
	* 获得供应商活动规则列表
	* @param 	$act_id 	    	能使用的活动id
	* @param 	$act_store_price	参与活动的金额
	* @param 	$act_type 	   		活动类型，1为满就减，2为满就折
	* @param 	$is_all_val    		全部商品/部分商品
	* @return 	$act_rule_list 		可使用的供应商活动规则列表
	*/
	private function get_act_rule($act_id,$act_store_price,$act_type,$is_all_val){

		//金额满足能使用的规则
		if($act_type == 1 || $act_type == 2){//满减、满折
			$act_rule_list = M('pms_activity_rule')->where('act_id='.$act_id.' and '.$act_store_price.'>=full_money');
			//$GLOBALS['db']->getAll("select * from ".DB_PREFIX."pms_activity_rule where act_id=".$act_id." and ".$act_store_price.">=full_money");
		}
		if($act_type == 3){//购物赠券,一个活动对应一个规则

			//规则：达到满足金额。优惠券：1.有效 2.有效期内 3.本门店领取张数合计未超过限制次数 4.赠送张数小于发行总张数
			$act_rule_list =M()->query("select par.*,pc.discount_money as coupon_price,pc.id as coupon_id,pc.limit_num from fw_pms_activity_rule as par left join fw_pms_coupon as pc on par.discount_money=pc.id where par.act_id=".$act_id." and ".$act_store_price.">=par.full_money and pc.is_del=0 and (unix_timestamp() between pc.start_time and pc.end_time) and pc.give_num<pc.total_num"); 
			

			if($act_rule_list[0]['coupon_id']){
				//判断门店之前购物赠券(type=1)次数是否已超
				$coupon_list =M('pms_location_coupon')->where('coupon_id='.$act_rule_list[0]['coupon_id'].' and location_id='.$this->get_location_ids().' and type=1');
			
				if($coupon_list){
					$location_coupon_num = 0;
					foreach ($coupon_list as $k => $v) {
						$location_coupon_num += $v['give_num'];
					}
					//如果门店领取的优惠券数量大于或等于限制次数，则清空
					if($location_coupon_num >= $act_rule_list[0]['limit_num']){
						$act_rule_list = '';
					}

				}

			}

		}
		

		if($act_rule_list){
			foreach ($act_rule_list as $k => $v) {

				$act_rule_list[$k]['act_type'] = $act_type;
				$act_rule_list[$k]['act_store_price'] = $act_store_price;
				if($act_type == 1)
					$act_rule_list[$k]['act_name'] = $is_all_val.'满'.round($v['full_money'],2).'减'.round($v['discount_money'],2);
				if($act_type == 2)
					$act_rule_list[$k]['act_name'] = $is_all_val.'满'.round($v['full_money'],2).'打'.round($v['discount_money'],2).'折';
				if($act_type == 3){
					$act_rule_list[$k]['act_name'] = $is_all_val.'满'.round($v['full_money'],2).'送'.round($v['coupon_price'],2).'元优惠券';
				}
					
			}
		}
		return $act_rule_list;
	}

	/**
	* 提交订单时判断 1.是否能使用活动规则 2.是否能使用优惠券
	* @param 	$act_rule_id 活动规则id列表 逗号隔开 一个供应商一个规则
	* @param 	$coupon_ids  门店优惠券列表 逗号隔开 一个供应商一张优惠券
	* @param 	$datas 		 二维数组，键值是供应商id。包含goods_id数组和对应的price小计数组
	* @return 	$new_datas	 返回二维数组，键值是供应商id。包含原始总金额、总金额、优惠总金额、参与活动金额、活动id、门店优惠券id
	*/
	private function use_act_coupon($act_rule_id,$coupon_ids,$datas){

		// 1.参与活动
		if($act_rule_id){
			//有效期内、有效的活动规则
			$act_list =M()->query("select par.act_id,par.full_money,par.discount_money,pa.start_time,pa.end_time,pa.supplier_id,pa.goods_ids,pa.act_type from fw_pms_activity_rule as par left join fw_pms_activity as pa on pa.id=par.act_id where pa.is_del=0 and par.id in (".$act_rule_id.") and (unix_timestamp() between pa.start_time and pa.end_time)");

		}

		if($act_list){
			foreach ($act_list as $k => $v) {
				$act[$v['supplier_id']] = $v;
			}
		}

		foreach ($datas as $k => $v) {

			//初始化 活动金额、活动id、门店优惠券id、优惠金额 
			$new_datas[$k]['act_price'] = 0;
			$new_datas[$k]['act_id'] = 0;
			$new_datas[$k]['location_coupon_id'] = 0;
			$new_datas[$k]['discount_price'] = 0;

			foreach ($v['goods_id'] as $d_k => $d_v) {

				//原始总金额
				$new_datas[$k]['total_original_price'] += $v['price'][$d_k];
				//总金额(未扣除优惠)
				$new_datas[$k]['total_price'] += $v['price'][$d_k];

				if($act[$k]){//如果存在该供应商活动

					if($act[$k]['goods_ids'] == 0){//全部商品
						$new_datas[$k]['act_price'] += $v['price'][$d_k];
					}else{//部分商品
						$had_goods_id = explode(',', $act[$k]['goods_ids']);
						if(in_array($d_v, $had_goods_id)){
							$new_datas[$k]['act_price'] += $v['price'][$d_k];
						}
					}

				}

			}

		}

		foreach ($datas as $k => $v) {
			//判断是否能参与活动，获得优惠或折扣
			if($new_datas[$k]['act_price'] >= $act[$k]['full_money'] && $new_datas[$k]['act_price']>0 && $act[$k]['full_money']>0){

				$new_datas[$k]['act_id'] = $act[$k]['act_id'];

				if($act[$k]['act_type'] == 1 && $act[$k]['discount_money']>0 && $act[$k]['discount_money']<$act[$k]['full_money']){//1为满就减
					$new_datas[$k]['discount_price'] = $act[$k]['discount_money'];//优惠金额
					$new_datas[$k]['total_price'] -= $act[$k]['discount_money'];//总金额减去优惠金额
				}
				if($act[$k]['act_type'] == 2 && $act[$k]['discount_money']>0 && $act[$k]['discount_money']<10){//2为满就折
					$new_datas[$k]['discount_price'] = $new_datas[$k]['act_price']*((10-$act[$k]['discount_money'])/10);//折扣金额
					$new_datas[$k]['total_price'] -= $new_datas[$k]['act_price']*((10-$act[$k]['discount_money'])/10);//总金额减去折扣金额
				}

			}
		}

		//2.使用优惠券
		if($coupon_ids){
			$coupon_list = M('pms_location_coupon')->where("location_id=".intval($this->get_location_ids())." and num>0 and (unix_timestamp() between start_time and end_time) and id in (".$coupon_ids.")")->select();
			
		}

		if($coupon_list){
			foreach ($coupon_list as $k => $v) {
				$coupon[$v['supplier_id']] = $v;
			}
		}

		foreach ($datas as $k => $v) {

			foreach ($v['goods_id'] as $d_k => $d_v) {

				if($coupon[$k]){//如果存在优惠券

					if($coupon[$k]['goods_ids'] == 0){//全部商品
						$new_datas[$k]['coupon_act_price'] += $v['price'][$d_k];
					}else{//部分商品
						$coupon_goods_id = explode(',', $coupon[$k]['goods_ids']);
						if(in_array($d_v, $coupon_goods_id)){
							$new_datas[$k]['coupon_act_price'] += $v['price'][$d_k];
						}
					}

				}

			}

		}

		foreach ($datas as $k => $v) {
			// 判断是否能使用优惠券
			if($new_datas[$k]['coupon_act_price'] >= $coupon[$k]['full_money'] && $new_datas[$k]['coupon_act_price']>0 && $coupon[$k]['full_money']>0){

				$new_datas[$k]['location_coupon_id'] = $coupon[$k]['id'];

				if($coupon[$k]['discount_money']>0 && $coupon[$k]['discount_money']<$coupon[$k]['full_money']){
					$new_datas[$k]['discount_price'] += $coupon[$k]['discount_money']; //(活动优惠、折扣金额)+优惠券金额
					$new_datas[$k]['total_price'] -= $coupon[$k]['discount_money'];//总金额减去优惠券金额(已减活动金额)	
				}
				if($coupon[$k]['id']){
					$new_location_coupon_id[] = $coupon[$k]['id'];//用于减优惠券数量
				}

			}
		}

		//减优惠券数量
		if($new_location_coupon_id){
			M('pms_location_coupon')->where("id in(".implode(',', $new_location_coupon_id).")")->setDec('num');			
		}
		
		return $new_datas;

	}

	//判断商品库存
	public function goods_stock_info($goods_id,$goods_num){

		$location_id=$this->get_location_ids();

		//商品库存
		$goods_stock=intval(M('pms_goods')->where('id='.$goods_id)->getField('stock'));		
		
		//购物车已添加商品库存
		$cart_stock=intval(M('pms_erp_cart')->where('goods_id='.$goods_id.' and location_id='.intval($location_id))->sum('number'));
	
		//库存不足返回负数
		$stock=$goods_stock-$goods_num-$cart_stock;

		return $stock;		
	}

	//获取门店购物车中的商品数量
	public function get_location_cart_info(){
		$location_id=$this->get_location_ids();		
		$info=M()->query("select sum(number) as number,sum(price*number) as total_price from fw_pms_erp_cart where location_id=".$location_id." limit 1");
		return $info[0];
	}

	//返回当前登录门店id
	public function get_location_ids(){
		$account_info=session('account_info');
		return $account_info['location_ids'][0];
	}

}