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
    return $settings;
}
function order_ddelivery_hook($obj,$row,$rout) {


    if($rout =='END') {

        $data = search_ddelivery_delivery2();
        $dd = array_merge($data['self_way'], $data['courier_way']);
        $dataID = implode(',', $dd);
        $order_action_add = "
           <script type=\"text/javascript\"> var DDeliveryConfig = { DDeliveryID: [$dataID],
                                                                     url: \"phpshop/modules/ddelivery/class/mrozk/ajax.php\"};
                                                                     </script>
           <script type=\"text/javascript\" src='phpshop/modules/ddelivery/class/html/js/ddelivery.js'></script>
           <script type=\"text/javascript\" src='phpshop/modules/ddelivery/templates/ddelivery.js'></script>
           ";

        // Форма личной информации по заказу
        $cart_min=$obj->PHPShopSystem->getSerilizeParam('admoption.cart_minimum');
        if($cart_min <= $obj->PHPShopCart->getSum(false)) {
            /*
            $obj->set('DDid', '[' . $res[0] . ']');
            $obj->set('DDorderUrl', 'phpshop/modules/ddelivery/class/mrozk/ajax.php');
     
            $order_action_add=parseTemplateReturn('phpshop/modules/ddelivery/templates/main_order_forma.tpl',true);
            
            $obj->set('order_action_add',$order_action_add,true);
            */
            //$obj->set('order_action_add',$order_action_add,true);

           $obj->set('orderContent',    $obj->get('orderContent') . $order_action_add);

        }
    }
}

$addHandler=array
        (
            'order'=>'order_ddelivery_hook'
        );
?>