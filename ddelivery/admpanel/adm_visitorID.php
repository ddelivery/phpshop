<?php
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );

function addDDeliveryPanel( $data ){
    global $PHPShopGUI;
    try{
        //DDelivery
        $IntegratorShop = new IntegratorShop();
        $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
        $ddOrder = $ddeliveryUI->getOrderByCmsID($data['uid']) ;   // ( $_REQUEST['visitorID'] ) ;
        if( $ddOrder !== null )
        {
            $ddeliveryPrice =  $ddeliveryUI->getDeliveryPrice( $ddOrder->localId );
            $ddID = (empty($ddOrder->ddeliveryID)? 'Заявка на DDelivery.ru не создана': 'ID заявки на DDelivery.ru - ' . $ddOrder->ddeliveryID);
            $Tab1 = $PHPShopGUI->setField(__("DDelivery"), 'Стоимость доставки - ' . $ddeliveryPrice . '<br /> ' . $ddID, 'left');
            $PHPShopGUI->addTab(array("Доставка DDelivery",$Tab1,450));
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
    }
}


$addHandler=array(
    'actionStart'=>'addDDeliveryPanel',
    'actionDelete'=>false,
    'actionUpdate'=>'checkCreateDDelivery'
);
?>