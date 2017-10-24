<?php

if (!function_exists('price')) {
    /**
     * 精确价格，只保留俩位小数
     * @param  int|float $price 价格
     * @return float
     */
    function price($price)
    {
        return sprintf("%.02f", $price);
    }
}