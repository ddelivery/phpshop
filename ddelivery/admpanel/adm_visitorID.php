<?php
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );

function addDDeliveryPanel( $data ){

    global $PHPShopGUI;

    try{

        $IntegratorShop = new IntegratorShop();
        $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
        $ddOrder = $ddeliveryUI->getOrderByCmsID($data['uid']) ;   // ( $_REQUEST['visitorID'] ) ;

        if( $ddOrder !== null )
        {
            $ddeliveryPrice =  $ddeliveryUI->getOrderClientDeliveryPrice( $ddOrder);
            $ddID = (empty($ddOrder->ddeliveryID)? '������ �� DDelivery.ru �� �������': 'ID ������ �� DDelivery.ru - ' . $ddOrder->ddeliveryID);
            $Tab1 = $PHPShopGUI->setField(__("DDelivery"), '��������� �������� - ' . $ddeliveryPrice . '<br /> ' . $ddID, 'left');
            $Tab1 .= $PHPShopGUI->setField(__("���������� � ������"), '��� �������� - ' . (($ddOrder->type == 1)?'���������':'��������') . '<br /> ' .
                                              '���� - ' . $ddOrder->getPoint()['delivery_time_avg'] . ' ���' . '<br /> ' .
                                              '�������� - ' . iconv('UTF-8', 'windows-1251',$ddOrder->getPoint()['delivery_company_name']) );

            $PHPShopGUI->addTab(array("�������� DDelivery",$Tab1,450));

            if (file_exists( __DIR__ . '/gui/tab_cart.gui.php')) {
                require_once(__DIR__ . '/gui/tab_cart.gui.php');
            }else{
                return 'file not exist';
            }
            $Tab2 = tab_cart_ddelivery($data);

            $PHPShopGUI->addTab(array(__("������� ��� DDelivery"), $Tab2, 350));

        }
    }catch ( \DDelivery\DDeliveryException $e){
        $ddeliveryUI->logMessage($e);
    }

}

function checkCreateDDelivery( $post ){
    global $PHPShopModules, $PHPShopOrm;

    $data = $PHPShopOrm->select(array('*'), array('id' => '=' . intval($_POST['visitorID'])));

    try{
        //DDelivery
        $IntegratorShop = new IntegratorShop();
        $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
        $ddOrder = $ddeliveryUI->getOrderByCmsID($data['uid']) ;   // ( $_REQUEST['visitorID'] ) ;

        if( $ddOrder !== null )
        {

           echo $ddeliveryUI->onCmsChangeStatus( $data['uid'], $post['statusi_new']);
        }
    }catch ( \DDelivery\DDeliveryException $e){
        $ddeliveryUI->logMessage($e);
        echo $e->getMessage();
        exit();
    }


}


$addHandler=array(
    'actionStart'=>'addDDeliveryPanel',
    'actionDelete'=>false,
    'actionUpdate'=>'checkCreateDDelivery'
);
?>