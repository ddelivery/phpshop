<?php
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );

/**
 * Created by PhpStorm.
 * User: mrozk
 * Date: 6/18/14
 * Time: 1:26 PM
 */
function action_order_ddelivery_hook()
{
    $formaContent = $GLOBALS['SysValue']['other']['formaContent'];
    $IntegratorShop = new IntegratorShop();
    $order_id =  $_GET['order'];

    try
    {
        $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
        $order = $ddeliveryUI->getOrderByCmsID($order_id);

        if($order)
        {

            $clientPrice = $order->getPoint()->getDeliveryInfo()->clientPrice;

            //$pos = strpos($formaContent, '0 руб.');
            $GLOBALS['SysValue']['other']['formaContent'] = str_replace( 'colspan="">0 ', 'colspan="">' . $clientPrice . ' ' ,
                                                                       $formaContent);
        }

        /*
        $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
        $ddeliveryUI->getOrderByCmsID();
        $order = $ddeliveryUI->initOrder(array($id));
        $clientPrice = $order[0]->getPoint()->getDeliveryInfo()->clientPrice;
        echo $clientPrice;
        echo $obj->delivery;
        */
    }
    catch(\DDelivery\DDeliveryException $e)
    {
        exit( 'asdasdasdasdassadasd' );
    }

}
$addHandler=array
(
    'action_order'=>'action_order_ddelivery_hook',
);