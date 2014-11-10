<?php
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );

/**
 * Настройка модуля
 */

function search_ddelivery_delivery(){

    $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['ddelivery']['ddelivery_system']);
    $data = $PHPShopOrm->select(array('settings'), array('id' => '=1'));
    if( !isset($data['settings']) || empty( $data['settings']) ){
        $settings = array('self_way' =>array(), 'courier_way' => array());
    }else{
        $settings = json_decode($data['settings'], true);
    }
    $dd = array_merge($settings['self_way'], $settings['courier_way']);
    return $dd;
}

/**
 * Хук
 */
function delivery_hook($obj, $data){
    $_RESULT=$data[0];
    $xid=$data[1];

    $dd = search_ddelivery_delivery();

    if( is_array($dd) && in_array($xid, $dd) ){
        $ddID = (int)$_POST['order_id'];
        if( $ddID ){
            try{
                $IntegratorShop = new IntegratorShop();
                $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
                $order = $ddeliveryUI->initOrder($ddID);
                $deliveryPrice = $ddeliveryUI->getOrderClientDeliveryPrice( $order );
                $hook['delivery'] = $deliveryPrice;
                $hook['total']= $_RESULT['total'] + $deliveryPrice;
            }catch (\DDeliveryException $e){
                $ddeliveryUI->logMessage($e);
            }
        }
        $hook['adresList'] = $_RESULT['adresList'];
        $hook['dellist'] = '<table collspan="0" rowspan="0"><tr><td>' . $_RESULT['dellist'] . '</td><td >' . '<a href="javascript::void(0)" onclick="DDeliveryIntegration.openPopup()">Выбрать способ доставки</a>' . '</td></tr></table>';
        /*
        $hook['dellist'] = '<table collspan="0" rowspan="0"><tr><td>' . $_RESULT['dellist'] . '</td><td valign="top" >' .
                            '<button class="ddbutton" href="javascript::void(0)" style="padding:7px 9px 6px;display:block;background-color:#FDBC6E;color:white;border:0px;margin-left:10px;" onclick="DDeliveryIntegration.openPopup();return false;" id="ddbutton" >Выбрать способ доставки</button>' .
                            '
                            <div class="dd_comment" style="color: #FDAA5E;width:200px;padding:10px 10px"></div>
                            </td></tr> </table>';
        */
        return  $hook;
    }
}

$addHandler = array(
    'delivery' => 'delivery_hook'
);
?>
