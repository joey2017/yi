<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
include(Yii::$app->BasePath."/views/layouts/header.php");
?>
<link rel="stylesheet" href="<?=SITE_URL?>/font-awesome/css/font-awesome.min.css">
</head>


<body>

<style type="text/css">
/*布局样式重置*/
.tab_parent{padding-left: 15px;}
.tab_subset{margin:0; padding: 0 15px 0 0;}
a{color: #333;}
a:focus,a:active, a:hover{color: #333; text-decoration: none;}
.box_flex{font-size:14px; display: -webkit-box; display: -moz-box; display: -webkit-flex; display: -moz-flex; display: -ms-flexbox; display: flex;}
.flex1{ -webkit-box-flex: 1; -moz-box-flex: 1; -webkit-flex: 1; -ms-flex: 1; flex: 1;}
body{position: relative;}

.scroller{height: 320px;}
.scroller img{height: 100%;}
.xqtitle h1{display: block; margin-top: 5px; flex-basis: 1px;  display: -webkit-box;  -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    word-wrap: break-word;  overflow: hidden;  -webkit-box-pack: center;  line-height: 1.35em;  height: 3em; font-size: 14px; color: #051B28;}


.setbox{width: 100px;}
.set_meal{padding: 0;}
.set_meal li{overflow: hidden; margin-bottom: 7px;}
.min,.add{ width: 30px; text-align: center;background-color: #E6E6E6; padding: 0; border:0;  height: 30px;  line-height: 30px;}
.min{border-radius: 3px 0 0 3px;}
.add{border-radius:0 3px 3px 0;}
.text_box{width: 40px; text-align: center; height: 30px; border: 0; margin: 0 -4px; line-height: 30px;box-shadow: none;border-radius: 0;    background: #fbfbfb;
}

.price_quantity{overflow: hidden;}
.price_quantity p{margin-bottom: 5px;}
.price{ color: #eb5211;font-size: 20px;}

.shop_name{font-size: 12px; margin-bottom: 12px;}

.j_indPanel{ width: 100px;}
.j_indPanel .pull-right{font-size: 12px;}
.sold{margin: 6px 0;}

/* 选项卡 */
.nav-tabs{ background: #efefef; border-top: #c40000 1px solid;}
.nav-tabs>li>a{border-radius: 0;}
.tab-pane img{width: 100%;}
.tab-content{padding:10px 10px 60px 10px;}

/*底部按钮*/
.sift_bottom{position:fixed; bottom: 50px; right: 0; width: 100%;}
.sift_bottom button{border:0;}
.sift-btn{ height: 48px;color: #fff;  line-height: 48px; float: left; text-align: center;}
.sift-btn button{background: #e7aa0e;}
.sift-btn-ok button{background: #ea413e;color: #fff;}
.sift-btn-no button {background: #adabab;color: #fff;}

.customer_qq { width: 50px;background: #eee;}
.customer_qq a{ text-align: center;padding-top: 6px; font-size: 12px;color: #8a8a8a;  }
.customer_qq a i{font-size: 22px;color: #6b9af5; display: block;}

/*焦点图*/
.swiper-container{height: auto;}
.xqtitle h1{display: block;  flex-basis: 1px;  display: -webkit-box;  -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    word-wrap: break-word;  overflow: hidden;  -webkit-box-pack: center;  line-height: 1.35em;  height: 3em; font-size: 14px; color: #051B28;}
.swiper-slide a{width: 100%; text-align: center;}
.swiper-slide a img{width: 100%;}

/*购物车按钮*/
.shoppingCart, .purchase_index{position: fixed; bottom: 60px;   width: 36px; height: 36px; background: rgba(0,0,0,0.5);
    filter: alpha(opacity=50);border-radius: 22px;}
.shoppingCart{left: 10px;}
.purchase_index{left: 54px; }
.shoppingCart a, .purchase_index a{   width: 36px; height: 36px;}
.shoppingCart a{background: url(<?=SITE_URL?>/images/shoppingCart.svg) no-repeat center;background-size: 20px;}
.purchase_index a{background: url(<?=SITE_URL?>/images/purchase_index.svg) no-repeat center;background-size: 30px;}
</style>

<div class="alertBg" id="msgBox" style="display:none;">
    <h4 class="alerttitle" id="alerttitle"></h4>
    <span class="vm f20" id='alertdetail'></span>
</div>

<!--焦点图-->
<div class="swiper-container">
    <div class="swiper-wrapper">
    	<div class="swiper-slide">
	        <img src="<?=$info['thumbnail']?>" >
	      </div>
    <?php if($info['imgs']!=null):?>
		<?php foreach($info['imgs'] as $img):?>
		      <div class="swiper-slide">
		        <img src="<?=$img?>!purchase" >
		      </div>
		<?php endforeach;?>
	<?php endif;?>	
   
    </div>
     <div class="swiper-pagination"></div>
</div>

<link rel="stylesheet" href="<?=SITE_URL?>/css/swiper.min.css">
<script type="text/javascript" src="<?=SITE_URL?>/js/swiper.min.js"></script>

<script>
    var swiper = new Swiper('.swiper-container', {
        pagination: '.swiper-pagination',
        paginationClickable: true,
        spaceBetween: 30,
        centeredSlides: true,
        autoplay: 2000,
        autoplayDisableOnInteraction: false
    });


</script>

<div class="col-xs-12">
	<div class="xqtitle">
		<h1><?=$info['goods_name']?></h1>
	</div>
	<div class="price_quantity box_flex">
		<div class="flex1">
			<p><?php if($info['promotion_price']!=0):?>促销价<?php else:?>批发价<?php endif;?><span class="price">￥<?php if($info['promotion_price']!=0):?><?=price($info['promotion_price'])?><?php else:?><?=price($info['price'])?><?php endif;?></span> <?php if($info['unit'] != 0):?>/<?=$info['unit']?><?php endif;?></p>
			<?php if($info['promotion_price']!=0):?>
				<p style="font-size: 12px;">批发价<del><span class="">￥<?=price($info['price'])?> <?php if($info['unit']!= 0):?>/<?=$info['unit']?><?php endif;?></span></del></p>
			<?php endif;?>
			<p style="font-size: 12px;">零售价<span class="">￥<?=price($info['market_price'])?> <?php if($info['unit']!=0):?>/<?=$info['unit']?><?php endif;?></span></p>
		</div>
		<div class="pull-right">
			<div class="setbox">
				<input class="min" name="" type="button" value="-" />
				<input class="text_box" type="number" name="goods_num" id="goods_num"  type="text" value="1" />
				<input class="add" name="" type="button" value="+" />
			</div>
			<div class="j_indPanel">
				<span class="pull-right sold">已售 <?=$info['sales']?></span>
			</div>
		</div>
		<script type="text/javascript">

			// 项目选择 
			$(function(){
				$(".add").click(function() {
			        $(this).prev().val(parseInt($(this).prev().val()) + 1);
			        //setTotal();
				});
				 
				$(".min").click(function() {
			        var tt = $(".text_box").val();
			        if(tt<=0){
			        	return false;
			        }else{
				        $(this).next().val(parseInt($(this).next().val()) - 1);
				        //setTotal();
				    }
				});
			})
		</script>	
	</div>
	<div><span class="pull-right price" style="margin-bottom:10px;font-size: 12px; margin-top: -5px;"><?=$info['supplier_name']?></span></div>
	
</div>

<div style="clear: both;">
	<ul class="nav nav-tabs box_flex" id="myTab">
	  	<li class="flex1"><a href="#details" data-toggle="tab">商品详情</a></li>
        <li class="flex1"><a href="#parameter" data-toggle="tab">规格参数</a></li>
        <li class="flex1"><a href="#deal" data-toggle="tab">适用车型</a></li>
	</ul>
</div>
<div class="tab-content">
	<div class="tab-pane" id="details">
	<?=$info['detail']?>
	</div>
	<div class="tab-pane" id="parameter">
		<table class="table table-bordered">
			<?php foreach($attr_names as $key => $an):?>
				<tr>
					<td align="right" width="120" class="tdbg"><?=$an?></td>
					<td><?=$attr_vals[$key]?></td>
				</tr>
			<?php endforeach;?>
		</table>
	</div>
	<div class="tab-pane" id="deal">
		<table class="table table-bordered">
			<?php foreach($car as $c):?>
				<tr>
					<td align="right" width="120" class="tdbg"><?=$c['cate2']?></td>
					<td><?=$c['cate3']?></td>
				</tr>
			<?php endforeach;?>
		</table>
	</div>
</div>

<div class="sift_bottom box_flex">
	<?php if($info['qq']):?>
		<div class="customer_qq">
			<a href="http://wpa.qq.com/msgrd?v=3&uin=<?=$info['qq']?>&site=qq&menu=yes" class="btn-block"><i class="fa fa-commenting-o" aria-hidden="true"></i>
			客服</a>
		</div>
	<?php endif;?>
	<div class="sift-btn flex1">
		<button class="btn-block" id="add_card" onclick="add_cart(1)">加入购物车(<span id="cart_num"><?=$cart_num?></span>)</button>
	</div>
	<div class="sift-btn flex1 sift-btn-ok">
		<button onclick="add_cart(2)" class="btn-block">立即购买</button>
	</div>
</div>

<!--底栏-->
<?php
include(Yii::$app->BasePath."/views/layouts/purchase_bottom.php");
?>

<script type="text/javascript">
		function add_cart(t){
			var goods_num=parseInt($('#goods_num').val()),stock=parseInt(<?=$info['stock']?>),goods_id=parseInt(<?=$info['id']?>);	
			if(goods_num<=0){
				MsgBox('请输入正常购买数量');
				return false;
			}

			$.ajax({
		        url:"<?=Url::toRoute('add-card')?>",
		        type:"POST",
		        data:{
		          "goods_num":goods_num,
		          "goods_id":goods_id
		        },
		        dataType:"json",
		        success:function(data){ 
		        	if(data.status==-1){
		        		MsgBox(data.info);
		        		setTimeout(function(){
		        			location.href=data.url;
		        		},2000)
		        	}else{
		        		if(data.status){
			            	if(t==1){
			            		MsgBox('添加成功');
					            $("#cart_num").html(data.stock);
			            	}else{
			            		location.href="<?=Url::toRoute('cart')?>";
			            	}         			
					          	
			            }else{
			            	MsgBox(data.info);
			            	
			            }
		        	}       		
		            
			    }
			});

		}
	    $('#myTab a[href="#details"]').tab('show'); 
</script>
<script type="text/javascript">
document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
  WeixinJSBridge.call('hideToolbar');
  WeixinJSBridge.call('hideOptionMenu');
});
</script>
</body>
</html>
