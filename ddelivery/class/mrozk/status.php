<?php
/**
 * Created by PhpStorm.
 * User: mrozk
 * Date: 12.05.14
 * Time: 22:35
 */

session_start();

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
    $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
    $ddeliveryUI->getPullOrdersStatus();

    //$orders = $ddeliveryUI->getAllOrders();
    //print_r($orders);

}
catch (\DDelivery\DDeliveryException $e)
{
    echo $e->getMessage();
}