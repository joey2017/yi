<include file="Inc:header"/>

</head>

<body>
<style type="text/css">
    /*支付方式*/
.payul{padding: 14px 0 0 0;}
.payul li{ list-style: none;  position: relative; padding: 10px; text-align: left; overflow: hidden; border: 1px solid #d2d2d2; cursor: pointer;  font-size: 12px;    margin-left: 2px;}
.payul li .pay_ico{  margin-right: 4px; font-size: 16px;vertical-align: middle;}
.payul li em{display: none;}
.pay_1{color: #48D238;}
.pay_2{color: #F34949;}
.pay_3{color: #49B2FD;}

.disabled .pay_1,.disabled .pay_2,.disabled .pay_3 {color: #ccc;}

.disabled {color: #ccc;}
.disabled span{ background-color: #ccc;}

.selected_pay{padding: 9px !important; border: 2px solid #ff5200 !important;}
.selected_pay em{display: block !important; position: absolute; bottom: -11px;  right: -16px; width: 35px; text-align: center; height: 28px; background: #ff5200; color: #fff; position: absolute; transform: rotate(-45deg); -o-transform: rotate(-45deg); -webkit-transform: rotate(-45deg); -moz-transform: rotate(-45deg);} 
.selected_pay em  i{transform: rotate(45deg); -o-transform: rotate(45deg); -webkit-transform: rotate(45deg); -moz-transform: rotate(45deg); } 
</style>

<!--订单名称-->
<div class="container-fluid" >
    <div class="row ddxq" style="line-height:27px; padding-top:10px; padding-bottom:10px;">
        <div class="col-xs-12"><p>交易编号：<{$oi.order_sn}></p></div>
    </div>
     <div class="row ddxq">
        <div class="col-xs-12"><p>创建时间：<{$oi.create_time|date="Y-m-d H:i:s",###}></p></div>
    </div>
     <div class="row ddxq">
        <div class="col-xs-12"><p>订单总价：<{$oi.total_price|price}>元</p></div>
    </div>
</div>




<style type="text/css">
.wxtxt {color: #b9b9b9;}
.play_span{ font-size: 31px; margin-right: 10px; margin-top: 7px;  width: 40px;  text-align: center; }
</style>
<link href="http://s.17cct.com/v5/css/font-awesome.min.css" rel="stylesheet">
<div class="alertBg" id="msgBox" style="display:none;">
    <h4 class="alerttitle" id="alerttitle"></h4>
    <span class="vm f20" id='alertdetail'></span>
</div>
<!--支付方式-->
<div class="container-fluid" >
	<div class="row ddxq">
			<div class="col-xs-8">您需要支付:</div>
            <div class="col-xs-4"><p style="float:right; color:#ff7302;"><strong><{$oi.total_price|price}>元</strong></p></div>
	</div>
    <if condition="$oi['pay_status'] eq 0 && $oi['type'] eq 0">
       

        <div class="row ddxq">
            <div class="col-xs-10" style="margin-bottom:10px;">
            	<img src="__PUBLIC__/images/weixin.png" width="30" height="30" class="wxico">
                <h3 class="wxzf">微信支付</h3>
                <p class="wxtxt">推荐安装微信5.0及以上版本使用</p>
            </div>
            <div class="col-xs-2">
                <input type="radio" name="pay_mode" value="2" checked style="float:right; margin-top:20px;">
            </div>
        </div>
        

        <div class="row ddxq">
            <div class="col-xs-10" style="margin-bottom:10px;">
                <span class="pull-left play_span" style="color: #f9ae21;"><i class="fa fa-cny"></i></span>
                <h3 class="wxzf">现金支付</h3>
                <p class="wxtxt"></p>
            </div>
            <div class="col-xs-2">
                <input type="radio" name="pay_mode" value="3" style="float:right; margin-top:20px;">
            </div>
        </div>

        <div class="row ddxq">
            <div class="col-xs-10" style="margin-bottom:10px;">
                <span class="pull-left play_span" style="color: #77d4e4;"><i class="fa fa-credit-card"></i></span>
                <h3 class="wxzf">刷卡支付</h3>
                <p class="wxtxt"></p>
            </div>
            <div class="col-xs-2">
                <input type="radio" name="pay_mode" value="4" style="float:right; margin-top:20px;">
            </div>
        </div>

        <div class="row ddxq">
            <div class="col-xs-10" style="margin-bottom:10px;">
                <span class="pull-left play_span" style="color: #f56f6f;"><i class="fa fa-exchange"></i></span>
                <h3 class="wxzf">转账支付</h3>
                <p class="wxtxt"></p>
            </div>
            <div class="col-xs-2">
                <input type="radio" name="pay_mode" value="5" style="float:right; margin-top:20px;">
            </div>
        </div>
        <div id="line_div" style="display:none">
            <textarea class="form-control" rows="2" placeholder="填写支付备注" style="margin-top: 15px;" id="pay_remark"></textarea>
            <ul class="text-center flex payul">
                <li class="col-xs-inner  disabled" data='1'>
                    <i class="fa fa-yen pay_ico pay_1"></i>现金挂账
                    <em><i class="fa fa-check"></i></em>
                </li>
                <li class="col-xs-inner disabled" data='2'>
                    <i class="fa fa-calendar pay_ico pay_2"></i>月底挂账
                    <em><i class="fa fa-check"></i></em>
                </li>
                <li class="col-xs-inner disabled" data='3'>
                    <i class="fa fa-edit pay_ico pay_3"></i>约定挂账
                    <em><i class="fa fa-check"></i></em>
                </li>
            </ul>
        </div>
    <else/>
         <div class="row ddxq">              
            <div class="col-xs-12"><p>确认方式：<{$oi.pay_type}></p></div>                 
        </div>
    </if>
</div>




<div class="container-fluid" >

    <div class="row">
        
            <div class="col-xs-12" style="margin-top:25px;"><center><button type="button" class="btn btn-danger btn-lg" <if condition="$oi['pay_status'] eq 0 && $oi['type'] eq 0"> id="submit"   <else/> id="confirm"  </if>style="padding-left:40px;padding-right:40px;">确认订单</button></center></div>
       
    </div>
</div>


<script type="text/javascript">
    $('.col-xs-inner').click(function(){
        if(!$(this).hasClass('disabled'))
        $(this).addClass('selected_pay').siblings().removeClass('selected_pay');
    })
</script>


<!--下面的空格要保留-->
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>

<script type="text/javascript">
    $('input:radio[name="pay_mode"]').click(function(){
       if($(this).val()==2){
            $('#line_div').hide();
            $('.col-xs-inner').removeClass('selected_pay').addClass('disabled');
       }else{
        $('#line_div').show();
        $('.col-xs-inner').removeClass('disabled');
       }
    })
    $('#submit').click(function(){
       
        var pay_mode=parseInt($('input:radio[name="pay_mode"]:checked').val()),pay_type=0,pay_remark=$('#pay_remark').val();        
        if(pay_mode==2){
            window.location.href="<{:U('Pay/purchase_go_pay')}>?id=<{$oi.id}>";
        }else{
             $('.col-xs-inner').each(function(){
                if($(this).hasClass('selected_pay')){
                    pay_type=$(this).attr('data');
                }
             })

             if(pay_type==0){
                MsgBox('线下支付请选择挂账方式');
                return false;
             }
             if(pay_remark==''){
                MsgBox('线下支付请填写支付备注');
                return false;
             }
            $('#submit').attr('disabled',true);
            $.ajax({
                    url:"<{:U('Purchase/offline_pay')}>",
                    type:"post",
                    data:{'pay_mode':pay_mode,'pay_remark':pay_remark,'pay_type':pay_type,'id':<{$oi.id}>},
                    dataType:"json",
                    success:function(data){  
                        MsgBox(data.msg)
                        if(data.status == 1){
                           window.location.href="<{:U('Purchase/pay_back')}>?id=<{$oi.id}>";
                        }else{
                            $('#submit').attr('disabled',false);
                        }
                }
            }); 
        }
    })

    $('#confirm').click(function(){
         $('#confirm').attr('disabled',true);
          $.ajax({
                    url:"<{:U('Purchase/confirm_order')}>",
                    type:"post",
                    data:{'id':<{$oi.id}>},
                    dataType:"json",
                    success:function(data){ 
                        MsgBox(data.msg)
                        if(data.status == 1){
                           window.location.href="<{:U('Purchase/pay_back')}>?id=<{$oi.id}>&type=confirm";
                        }else{
                            $('#confirm').attr('disabled',false);
                        }
                }
            }); 
    })
</script>
<script type="text/javascript">
document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
  WeixinJSBridge.call('hideToolbar');
  WeixinJSBridge.call('hideOptionMenu');
});
</script>
</body>
</html>
