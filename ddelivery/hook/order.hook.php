<?php

/**
 * Добавление кнопки быстрого заказа
 */


function search_ddelivery_delivery2(){

    $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['ddelivery']['ddelivery_system']);
    $data = $PHPShopOrm->select(array('settings'), array('id' => '=1'));

    $dd = array();

    if( !isset($data['settings']) || empty( $data['settings']) ){
        $settings = array('self_way' => array(), 'courier_way' => array());
    }else{
        $settings = json_decode($data['settings'], true);
    }


    $dd = array_merge($settings['self_way'], $settings['courier_way']);
    return implode(',', $dd);
}

function order_ddelivery_hook($obj,$row,$rout) {
    global $PHPShopGUI;
    if($rout =='END') {
        $cart_min=$obj->PHPShopSystem->getSerilizeParam('admoption.cart_minimum');
        if($cart_min <= $obj->PHPShopCart->getSum(false)) {
            $data = search_ddelivery_delivery2();

            //$dd = implode(',', $data);
            $obj->set('DDid', '[' . $data . ']');
            $obj->set('DDorderUrl', 'phpshop/modules/ddelivery/class/mrozk/ajax.php');
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