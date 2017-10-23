<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
include(Yii::$app->BasePath."/views/layouts/header.php");
?> 
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
    .o_f{overflow: hidden;}
    .locate{position: relative;}
    .absolute_fix{position:absolute;}

    .new_topbg{height: 220px; background:url(<?php echo SITE_URL;?>/images/new_stop_index.jpg) no-repeat center 100%;}
    .new_topbg h1{color: #fff; font-size: 24px; margin-top: 65px;}
    .new_index_logo{padding:20px 0 0 20px;}

    .new_index_icon{background: url(<?php echo SITE_URL;?>/images/new_stop_index_icon.svg) no-repeat;background-size: 440px; }
    .Purchase{background-position: 13px 21px;}
    .e_shop{background-position: -110px 19px;}
    .admini{background-position: -230px 19px;}
    .more{background-position: -333px 19px;}


    .new_stop_index_line{top: 0; left: 0; z-index: 2;}
    .index_btn{padding-top: 25px; padding-bottom: 25px;}
    .index_btn span{display: block; height: 80px; width: 80px; margin: 0 auto;}
    .left_border{border-left:1px #e7e7e7 solid;}
    .bottom_border{border-bottom:1px #e7e7e7 solid;}

    .new_bottom_line{background: #da1f1f; height: 10px; position: fixed; left: 0; bottom: 0; width: 100%;}



    @media (min-width:425px) {
        .new_topbg{background-size: 100%;}
    }
    .tuichu{  z-index: 1;  width: 36px;  height: 36px;  top: 24px; right: 20px;}
    .tuichuico{ display: inline-block;  width: 36px;  height: 36px; vertical-align: middle;background: url(http://www.17cct.com/mobile/Public/images/bossico.svg) no-repeat -53px 5px; background-size: 110px;}


</style>


<div class="new_topbg locate">
    <div class="new_index_logo">
        <img src="<?php echo SITE_URL;?>/images/lodlogo2.png" width="190">
    </div>
    <h1 class="text-center"><?php echo $n_location_name;?></h1>
   <!--  <div class="row" style="margin-top:15px;">
        <a href="<?php echo Url::toRoute('Biz/login_out');?>" class="btn btn-warning btn-block btn-lg">退出登录</a>
    </div> -->
    <a  href="<?php echo Url::toRoute('biz/login-out');?>" class="pull-right absolute_fix tuichu"><span class="tuichuico"></span></a>
</div>


<div class="text-center locate">
    <img src="<?php echo SITE_URL;?>/images/new_stop_index_line.svg" width="100%" class="absolute_fix new_stop_index_line" >

    <div class="col-xs-6 index_btn bottom_border">
        <a href="<?php echo Url::toRoute('purchase/home');?>">
            <span class="new_index_icon Purchase"></span>
            批发采购
        </a>
    </div>
    <div class="col-xs-6 index_btn left_border bottom_border">
        <a href="<?php echo Url::toRoute('store/view',array('id'=>$n_location_id));?>">
            <span class="new_index_icon e_shop"></span>
            微商城
        </a>
    </div>
    <div class="col-xs-6 index_btn bottom_border">
        <a href="<?php echo Url::toRoute('biz/shop_count');?>">
            <span class="new_index_icon admini"></span>
            门店管理
        </a>
    </div>
    <div class="col-xs-6 index_btn left_border bottom_border">
        <a href="<?php echo Url::toRoute('biz/entrance_more',array('id'=>$n_location_id));?>">
            <span  class="new_index_icon more"></span>
            更多
        </a>
    </div>
</div>


<div class="new_bottom_line"></div>
</body>
</html>
