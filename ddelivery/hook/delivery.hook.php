<?php
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );
/**
 * Настройка модуля
 */
function ddelivery_option()
{
    $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['pickpoint']['pickpoint_system']);
    return $PHPShopOrm->select();
}

/**
 * Поиск доставки по имени
 */
function search_ddelivery_delivery($city, $xid)
{
    $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['delivery']);
    $data = $PHPShopOrm->select(array('id'), array('city' => " REGEXP '" . $city . "'", 'id' => '=' . $xid,'is_folder'=>"!='1'"), false, array('limit' => 1));
    if (is_array($data))
        return $data['id'];
}

/**
 * Хук
 */
function delivery_hook($obj, $data)
{
    $_RESULT=$data[0];
    $xid=$data[1];
    $query = 'SELECT delivery_id FROM ddelivery_module_system WHERE id=1';
    $cur = mysql_query($query);
    $res = mysql_fetch_array($cur);

    $dd = explode( ',', $res[0] );
    if( is_array($dd) && in_array($xid, $dd) )
    {
        $ddID = (int)$_POST['order_id'];
        if( $ddID )
        {   try{

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
        $hook['dellist'] = '<table collspan="0" rowspan="0"><tr><td>' . $_RESULT['dellist'] . '</td><td >' .
                           '<a href="javascript::void(0)" onclick="DDeliveryIntegration.openPopup();" id="ddbutton" >Выбрать способ доставки</a>' .
                           '</td></tr>
                           </table>';
        return  $hook;
    }
}

$addHandler = array
    (
    'delivery' => 'delivery_hook'
);
?>
