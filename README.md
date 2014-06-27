phpshop
=======

Модуль обработки доставки DDelivery для интернет магазинов на движке phpshop

скопировать папку ddelivery в папку phpshop/modules/ .

Активировать модуль в административной панели.

Для работы модуля необходимо подправить 

Приблизительно в строке  179 в файле /phpshop/admpanel/order/adm_visitorID.php после строчки
// Компания
$Tab1 = $PHPShopGUI->setField(__("Компания"), $PHPShopGUI->setTextarea('person[org_name]', $order['Person']['org_name'], 'none', 200), 'left');


втавить следующий код:
//DDelivery
    include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
    include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );
    $IntegratorShop = new IntegratorShop();
    $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
    //print_r($_REQUEST['visitorID']);
    $ddOrder = $ddeliveryUI->getOrderByCmsID($_REQUEST['visitorID']) ;   // ( $_REQUEST['visitorID'] ) ;
    if( $ddOrder !== null )
    {
        $ddeliveryPrice =  $ddeliveryUI->getDeliveryPrice( $ddOrder->localId );
        $ddID = (empty($ddOrder->ddeliveryID)? 'Заявка на ddelivery.ru не создана': 'ID заявки на ddelivery.ru - ' . $ddOrder->ddeliveryID);
        $Tab1 .= $PHPShopGUI->setField(__("DDelivery"), 'Стоимость доставки - ' . $ddeliveryPrice . '<br /> ' . $ddID, 'left');
    }
//DDelivery
