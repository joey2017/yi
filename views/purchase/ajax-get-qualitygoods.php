<?php
use yii\helpers\Html;
use yii\helpers\Url;
?>
<?php foreach ($qulitygoods as $g):?>
	<div class="col-xs-6 tab_subset producttab">
		<div class="proinfo">
			<a href="<?=Url::toRoute('purchase/detail',array('id'=>$g['id']))?>" class="btn-block">
				<img src="<?=$g['thumbnail']?>!purchase">
				<p><?=$g['goods_name']?></p>
				<div class="d-main">
	    			<span class="price">￥:<?=price($g['price'])?></span>
	    			<span>/<?=$g['unit']?></span>
	    			<span class="pull-right">已售 <?=$g['sales']?></span>
	    		</div>
    		</a>
		</div>
	</div>
<?php endforeach;?>
