<?php
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );
/**
 * ��������� ������
 */
function ddelivery_option()
{
    $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['pickpoint']['pickpoint_system']);
    return $PHPShopOrm->select();
}

/**
 * ����� �������� �� �����
 */
function search_ddelivery_delivery($city, $xid)
{
    $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['delivery']);
    $data = $PHPShopOrm->select(array('id'), array('city' => " REGEXP '" . $city . "'", 'id' => '=' . $xid,'is_folder'=>"!='1'"), false, array('limit' => 1));
    if (is_array($data))
        return $data['id'];
}

/**
 * ���
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
                $order = $ddeliveryUI->getOrder();
                $deliveryPrice = $ddeliveryUI->getOrderClientDeliveryPrice( $order );
                $hook['delivery'] = $deliveryPrice;
                $hook['total']= $_RESULT['total'] + $deliveryPrice;
            }catch (\DDeliveryException $e){
                $ddeliveryUI->logMessage($e);
            }
        }
        $hook['dellist'] = '<table collspan="0" rowspan="0"><tr><td>' . $_RESULT['dellist'] . '</td><td >' . '<a href="javascript::void(0)" style="" class="trigger ddbutton">������� ������ ��������</a>' . '</td></tr></table>';
        return  $hook;
    }

    //$title_id = search_ddelivery_delivery($option['city'], $xid);
    /*
    $hook['dellist'] = '<table collspan="0" rowspan="0"><tr><td>' . $_RESULT['dellist'] . '</td><td style=" padding-left: 20px; padding-top: 36px;">' . 'putin hyjlo' . '</td></tr></table>';
    $hook['delivery']= 'moses';

    if (is_numeric($title_id))
        if ($xid == $title_id) {
            

            $button = '<a onclick="PickPoint.open(pickpoint_phpshop); return false" href="#">' . $option['name'] . '</a>';
            $hook['dellist'] = '<table collspan="0" rowspan="0"><tr><td>' . $_RESULT['dellist'] . '</td><td style=" padding-left: 20px; padding-top: 36px;">' . $button . '</td></tr></table>';
            $hook['delivery']=$_RESULT['delivery'];
            $hook['total']=$_RESULT['total'];

            return  $hook;
        }
    */
}

$addHandler = array
    (
    'delivery' => 'delivery_hook'
);
?>
