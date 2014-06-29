phpshop
=======

Модуль обработки доставки DDelivery для интернет магазинов на движке phpshop

скопировать папку ddelivery в папку phpshop/modules/ .

Активировать модуль в административной панели.

Выбрать в меню административной панели выбрать пункт Модули -> DDelivery -> Создать базу городов

Далее перейти Модули -> DDelivery -> Настройки и ввести api ключ из кабинета DDelivery

Для работы модуля в административной панели  необходимо подправить

Приблизительно в строке  179 в файле /phpshop/admpanel/order/adm_visitorID.php после строчки
// Компания
$Tab1 = $PHPShopGUI->setField(__("Компания"), $PHPShopGUI->setTextarea('person[org_name]', $order['Person']['org_name'], 'none', 200), 'left');

втавить следующий код:
//DDelivery
    include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
    include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );
    $IntegratorShop = new IntegratorShop();
    $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);

    $ddOrder = $ddeliveryUI->getOrderByCmsID($data['uid']) ;

    if( $ddOrder !== null )
    {
        $ddeliveryPrice =  $ddeliveryUI->getDeliveryPrice( $ddOrder->localId );
        $ddID = (empty($ddOrder->ddeliveryID)? 'Заявка на ddelivery.ru не создана': 'ID заявки на ddelivery.ru - ' . $ddOrder->ddeliveryID);
        $Tab1 .= $PHPShopGUI->setField(__("DDelivery"), 'Стоимость доставки - ' . $ddeliveryPrice . '<br /> ' . $ddID, 'left');
    }
//DDelivery

Приблизительно в строке  70 в файле /phpshop/admpanel/order/gui/tab_cart.gui.php после строчки

// Обнуляем вес товаров, если хотя бы один товар был без веса
    if ($zeroweight) {
        $weight = 0;
    }
вместо строчки $GetDeliveryPrice = $PHPShopOrder->getDeliverySumma();
втавить следующий код:

//DDelivery
    include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
    include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );
    $IntegratorShop = new IntegratorShop();
    $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);

    $ddOrder = $ddeliveryUI->getOrderByCmsID($data['uid']) ;

    if( $ddOrder !== null ){
        $ddeliveryPrice =  $ddeliveryUI->getDeliveryPrice( $ddOrder->localId );
        $ddID = (empty($ddOrder->ddeliveryID)? 'Заявка на ddelivery.ru не создана': 'ID заявки на ddelivery.ru - ' . $ddOrder->ddeliveryID);
        $GetDeliveryPrice = $ddeliveryPrice;
    }else{
        $GetDeliveryPrice = $PHPShopOrder->getDeliverySumma();
    }
//DDelivery




