<?php

function addDDeliveryPanel( $data ){
    global $PHPShopGUI;


    //DDelivery
    include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
    include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );
    $IntegratorShop = new IntegratorShop();
    $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
    //print_r($_REQUEST['visitorID']);
    $ddOrder = $ddeliveryUI->getOrderByCmsID($data['uid']) ;   // ( $_REQUEST['visitorID'] ) ;
    if( $ddOrder !== null )
    {
        $ddeliveryPrice =  $ddeliveryUI->getDeliveryPrice( $ddOrder->localId );
        $ddID = (empty($ddOrder->ddeliveryID)? '': 'ID заявки на ddelivery.ru - ' . $ddOrder->ddeliveryID);
        $Tab1 = $PHPShopGUI->setField(__("DDelivery"), 'Стоимость доставки - ' . $ddeliveryPrice . '<br /> ' . $ddID, 'left');

        //DDelivery

        //$Tab3.=$PHPShopGUI->setField('Скриншот',GetSkinsIcon($data['skincat']),$float="none",$margin_left=5);

        $PHPShopGUI->addTab(array("�������� DDelivery",$Tab1,450));
    }
    /*
        // Добавляем значения в функцию actionStart
        $Tab3=GetSkinList($data['skincat']);
        $Tab3.=$PHPShopGUI->setField('Скриншот',GetSkinsIcon($data['skincat']),$float="none",$margin_left=5);
        $PHPShopGUI->addTab(array("Skin",$Tab3,450));
     */
}




$addHandler=array(
    'actionStart'=>'addDDeliveryPanel',
    'actionDelete'=>false,
    'actionUpdate'=>false
);
?>