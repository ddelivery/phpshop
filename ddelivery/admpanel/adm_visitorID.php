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
            $ddID = (empty($ddOrder->ddeliveryID)? 'Заявка на DDelivery.ru не создана': 'ID заявки на DDelivery.ru - ' . $ddOrder->ddeliveryID);
            $Tab1 = $PHPShopGUI->setField(__("DDelivery"), 'Стоимость доставки - ' . $ddeliveryPrice . '<br /> ' . $ddID, 'left');
            $Tab1 .= $PHPShopGUI->setField(__("Информация о заказе"), 'Тип доставки - ' . (($ddOrder->type == 1)?'Самовывоз':'Курьером') . '<br /> ' .
                                              'Срок - ' . $ddOrder->getPoint()['delivery_time_avg'] . ' дня' . '<br /> ' .
                                              'Компания - ' . iconv('UTF-8', 'windows-1251',$ddOrder->getPoint()['delivery_company_name']) );

            $PHPShopGUI->addTab(array("Доставка DDelivery",$Tab1,450));

            if (file_exists( __DIR__ . '/gui/tab_cart.gui.php')) {
                require_once(__DIR__ . '/gui/tab_cart.gui.php');
            }else{
                return 'file not exist';
            }
            $Tab2 = tab_cart_ddelivery($data);

            $PHPShopGUI->addTab(array(__("Корзина для DDelivery"), $Tab2, 350));

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