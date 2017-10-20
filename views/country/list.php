<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $model app\models\Country */

?>
<h1>Countries</h1>
<ul>
    <?php foreach ($countries as $country):?>
        <li>
            <?= Html::encode("{$country->name}({$country->code})")?>:
            <?= $country->population?>
        </li>
    <?php endforeach;?>
</ul>
<?= LinkPager::widget(['pagination' => $pagination]) ?>