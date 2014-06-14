<?php
/**
 * Created by PhpStorm.
 * User: mrozk
 * Date: 4/28/14
 * Time: 11:43 AM
 */
session_start();

header('Content-Type: text/html; charset=utf-8');

$_classPath="../../../../";
include($_classPath."class/obj.class.php");

PHPShopObj::loadClass("base");

$PHPShopBase = new PHPShopBase($_classPath."inc/config.ini");

PHPShopObj::loadClass("array");
PHPShopObj::loadClass("orm");
PHPShopObj::loadClass("product");
PHPShopObj::loadClass("system");
PHPShopObj::loadClass("valuta");
PHPShopObj::loadClass("string");
PHPShopObj::loadClass("cart");
PHPShopObj::loadClass("security");
PHPShopObj::loadClass("user");
PHPShopObj::loadClass("modules");

// Массив валют
$PHPShopValutaArray= new PHPShopValutaArray();

// Системные настройки
$PHPShopSystem = new PHPShopSystem();



include_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'application', 'bootstrap.php')));


include_once('IntegratorShop.php');

$IntegratorShop = new IntegratorShop();


// Turn off all error reporting
try
{
    $start_memory_usage = memory_get_usage(); $start_time = microtime(true);
    $end_memory_usage = memory_get_usage();
    $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop);

    $ddeliveryUI->cleanCache();
    $order = $ddeliveryUI->getOrder();
    $order->city = 151184;
    $order->type = 1;
    echo $order->amount . '<br />';

    echo 'zabor - ' . $IntegratorShop->cmsSettings['zabor'] . '<br />';
    echo 'dimensionSide1 -' . $order->dimensionSide1 . '<br />';
    echo 'dimensionSide2 -' . $order->dimensionSide2 . '<br />';
    echo 'dimensionSide3 -' . $order->dimensionSide3 . '<br />';
    echo 'getWeight -' . $order->getWeight() . '<br />';
    $points = $ddeliveryUI->getCourierPointsForCity( $order );
    echo '<pre>';
    print_r($points);
    echo '</pre>';

    //$order = $ddeliveryUI->getOrder();
   //print_r($order);
    //$order->city = 151185;
   //print_r( $ddeliveryUI->getSelfPoints($order) );

   // $ddeliveryUI->onCmsOrderFinish(107, 2,3,2);

    //$orders = $ddeliveryUI->getAllOrders();

    echo '<pre>';
    print_r($order);
    echo '</pre>';
    $end_time = microtime(true);

   // print_r($orders);
    /*
    foreach( $orders AS $item )
    {
        print_r($item);
        echo '<hr />';
    }
    */
}
catch (\DDelivery\DDeliveryException $e)
{
    echo $e->getMessage();
}


//echo $start_memory_usage . '<br />';
//echo $end_memory_usage;