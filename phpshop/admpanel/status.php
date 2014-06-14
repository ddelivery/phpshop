<?php
/**
 * Created by PhpStorm.
 * User: mrozk
 * Date: 12.05.14
 * Time: 22:35
 */

session_start();
header('Content-Type: text/html; charset=utf-8');
$_classPath="../../../";
include($_classPath . "class/obj.class.php");

PHPShopObj::loadClass("base");

$PHPShopBase = new PHPShopBase($_classPath."inc/config.ini");
include($_classPath."admpanel/enter_to_admin.php");

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



include_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'class', 'application', 'bootstrap.php')));
include_once(implode(DIRECTORY_SEPARATOR, array(__DIR__, '..', 'class', 'mrozk', 'IntegratorShop.php')));

$IntegratorShop = new IntegratorShop();

// Turn off all error reporting
try
{
    $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
    $ddeliveryUI->createTables();
    if( $_GET['action'] == '1' )
    {
        $pull = $ddeliveryUI->createPullOrders();
        if(count($pull))
        {
            foreach($pull as $p)
            {

                echo ' ddelivery order ID - ' . $p['ddId'] . '<br />';
                echo ' cms order ID - ' . $p['localID'] . '<hr/>';
            }
        }
        else
        {
            echo 'NO ORDERS';
        }

    }
    else if($_GET['action'] == '2')
    {
        $orders = $GLOBALS['SysValue']['base']['order_status'];

        $query = 'SELECT id,name FROM ' . $orders ;
        $cur = mysql_query($query);
        $result = array();
        while ($k = mysql_fetch_array($cur))
        {
            $n = iconv('CP1251','UTF-8',$k[1]);
            $result[$k[0]] = $n;
        }
        /*

        print_r($result);
        */
        $pull = $ddeliveryUI->getPullOrdersStatus();
        if(count($pull))
        {
            foreach($pull as $p)
            {
                echo ' cms order ID ' . $p['cms_order_id'] . '<br />';
                echo ' DDelivery status - ' .  $p['ddStatus'] . ' ' .
                     ' (' . $ddeliveryUI->getDDStatusDescription($p['ddStatus']) . ')<br />';
                echo ' CMS status  - ' . $p['localStatus'] . ' (' . $result[$p['localStatus']]   . ')<hr />';
            }
        }
        else
        {
            echo 'NO ORDERS';
        }
    }
}
catch (\DDelivery\DDeliveryException $e)
{
    echo $e->getMessage();
}
