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

$PHPShopValutaArray= new PHPShopValutaArray();

$PHPShopSystem = new PHPShopSystem();

ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'application', 'bootstrap.php')));


include_once('IntegratorShop.php');


$IntegratorShop = new IntegratorShop();


try
{

    $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, false);

    $order = $ddeliveryUI->getOrder();
    $order->type= 1;
    $ddeliveryUI->getAvailablePaymentVariants( $order );
    print_r($IntegratorShop->getSelfPaymentVariants( $orders ));


}
catch (\DDelivery\DDeliveryException $e)
{
    echo $e->getMessage();
}


//echo $start_memory_usage . '<br />';
//echo $end_memory_usage;