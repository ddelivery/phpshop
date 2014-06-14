<?php

/**
 * Добавление кнопки быстрого заказа
 */


function order_ddelivery_hook($obj,$row,$rout) {
    global $PHPShopGUI;
    //error_reporting(E_ALL);
    //$PHPShopGUI->addJSFiles('phpshop/modules/ddelivery/class/example/assets/jquery.min.js');
    if($rout =='END') {
        $query = 'SELECT delivery_id FROM phpshop_modules_ddelivery_system WHERE id=1';
        $cur = mysql_query($query);
        $res = mysql_fetch_array($cur);

        // Форма личной информации по заказу
        $cart_min=$obj->PHPShopSystem->getSerilizeParam('admoption.cart_minimum');
        if($cart_min <= $obj->PHPShopCart->getSum(false)) {
            $obj->set('DDid', $res[0]);
            $obj->set('bidloOrder', 'phpshop/modules/ddelivery/class/mrozk/ajax.php?' . ( isset($_GET['XDEBUG_SESSION_START']) ? 'XDEBUG_SESSION_START='.(int)$_GET['XDEBUG_SESSION_START'] : ''));
            $obj->set('orderContent',parseTemplateReturn('phpshop/modules/ddelivery/templates/main_order_forma.tpl',true));

        }
        else {
            $obj->set('orderContent',$obj->message($obj->lang('cart_minimum').' '.$cart_min,$obj->lang('bad_order_mesage_2')));
        }



    }
}

$addHandler=array
        (
            'order'=>'order_ddelivery_hook'
        );
?>