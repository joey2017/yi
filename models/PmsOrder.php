<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "SupplierAccount".
 *
 * @property string $code
 * @property string $name
 * @property integer $population
 */
class PmsOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'fw_pms_order';
    }
}
