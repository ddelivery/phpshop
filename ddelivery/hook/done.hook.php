<?php

include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );
/**
 * E-mail XML файла заказа
 */

function mail_ddelivery_hook($obj,$row,$rout) {

    if($rout == 'START' and !empty($row['ddelivery_order_id'])){



        $id =  (int) $row['ddelivery_order_id'];
        $IntegratorShop = new IntegratorShop();
        try{
            $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);

            $order = $ddeliveryUI->initOrder($id);
            $clientPrice = $ddeliveryUI->getOrderClientDeliveryPrice( $order );
            //echo $clientPrice;
            //echo $obj->delivery;
        }catch(\DDelivery\DDeliveryException $e){
            $ddeliveryUI->logMessage($e);
        }
        $obj->set('cart', $obj->PHPShopCart->display('mailcartforma', array('currency' => $obj->currency)));
        $obj->set('sum', $obj->sum);
        $obj->set('currency', $obj->currency);
        $obj->set('discount', $obj->discount);
        $obj->set('deliveryPrice', $obj->delivery);
        $obj->set('total', $obj->total);
        $obj->set('shop_name', $obj->PHPShopSystem->getName());
        $obj->set('ouid', $obj->ouid);
        $obj->set('date', date("d-m-y"));
        $obj->set('name_person', $row['name_person']);
        $obj->set('tel', @$row['tel_code'] . "-" . @$row['tel_name']);
        $obj->set('adr_name', PHPShopSecurity::CleanStr(@$row['adr_name']));
        $obj->set('dos_ot', @$row['dos_ot']);
        $obj->set('dos_do', @$row['dos_do']);
        $obj->set('deliveryCity', $obj->PHPShopDelivery->getCity());
        $obj->set('mail', $row['mail']);
        $obj->set('payment', $obj->PHPShopPayment->getName());
        $obj->set('company', $obj->PHPShopSystem->getParam('company'));
        $content = ParseTemplateReturn('./phpshop/lib/templates/order/usermail.tpl', true);
        // Заголовок письма покупателю
        $title = $obj->PHPShopSystem->getName() . $obj->lang('mail_title_user_start') . $row['ouid'] . $obj->lang('mail_title_user_end');

        // Перехват модуля в середине функци

        // Отсылаем письмо покупателю
        $PHPShopMail = new PHPShopMail($row['mail'], $obj->PHPShopSystem->getParam('adminmail2'), $title, $content);

        $obj->set('shop_admin', "http://" . $_SERVER['SERVER_NAME'] . $obj->getValue('dir.dir') . "/phpshop/admpanel/");
        $obj->set('time', date("d-m-y H:i a"));
        $obj->set('ip', $_SERVER['REMOTE_ADDR']);
        $content_adm = ParseTemplateReturn('./phpshop/lib/templates/order/adminmail.tpl', true);

        // Заголовок письма администратору
        $title_adm = $obj->PHPShopSystem->getName() . ' - ' . $obj->lang('mail_title_adm') . $row['ouid'] . "/" . date("d-m-y");


        // Отсылаем письмо администратору
        $PHPShopMail = new PHPShopMail($obj->PHPShopSystem->getParam('adminmail2'), $row['mail'], $title_adm, $content_adm);
        return true;
    }

}

function write_ddelivery_hook($obj, $row, $rout)
{

    if($rout == 'START')
    {
        $person = array(
            "ouid" => $obj->ouid,
            "data" => date("U"),
            "time" => date("H:s a"),
            "mail" => $row['mail'],
            "name_person" => PHPShopSecurity::CleanStr(@$row['name_person']),
            "org_name" => PHPShopSecurity::CleanStr(@$row['org_name']),
            "org_inn" => PHPShopSecurity::CleanStr(@$row['org_inn']),
            "org_kpp" => PHPShopSecurity::CleanStr(@$row['org_kpp']),
            "tel_code" => PHPShopSecurity::CleanStr(@$row['tel_code']),
            "tel_name" => PHPShopSecurity::CleanStr(@$row['tel_name']),
            "adr_name" => PHPShopSecurity::CleanStr(@$row['adr_name']),
            "dostavka_metod" => @$row['dostavka_metod'],
            "discount" => $obj->discount,
            "user_id" => $_SESSION['UsersId'],
            "dos_ot" => PHPShopSecurity::CleanStr(@$row['dos_ot']),
            "dos_do" => PHPShopSecurity::CleanStr(@$row['dos_do']),
            "order_metod" => @$row['order_metod']);
        // Данные по корзине
        $cart = array(
            "cart" => $obj->PHPShopCart->getArray(),
            "num" => $obj->num,
            "sum" => $obj->sum,
            "weight" => $obj->weight,
            "dostavka" => $obj->delivery);

        // Серелиазованный массив заказа
        $obj->order = serialize(array("Cart" => $cart, "Person" => $person));
        // Данные для записи
        $insert = $row;
        $insert['datas_new'] = time();
        $insert['uid_new'] = $obj->ouid;
        $insert['orders_new'] = $obj->order;
        $insert['status_new'] = serialize($obj->status);
        $insert['user_new'] = $_SESSION['UsersId'];
        //exit($obj->ouid);
        // Запись заказа в БД
        $result = $obj->PHPShopOrm->insert($insert);
        $cmsID =   $obj->ouid;

        if( !empty($row['ddelivery_order_id'] ))
        {
            $id =  (int) $row['ddelivery_order_id'];
            PHPShopObj::loadClass("modules");

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


            $IntegratorShop = new IntegratorShop();
            try{

                $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
                $ddeliveryUI->onCmsOrderFinish($id, $cmsID, 0, @$row['order_metod']);
            }
            catch(\DDelivery\DDeliveryException $e){
                $ddeliveryUI->logMessage($e);
            }

        }

        // Проверка ошибок при записи заказа
        $obj->error_report($result, array("Cart" => $cart, "Person" => $person, 'insert' => $insert));

        // Принудительная очистка корзины
        $obj->PHPShopCart->clean();
        return true;

    }
}
function send_to_order_ddelivery_hook($obj,$row,$rout)
{
    global $SysValue;
    if($rout == 'START' && !empty($_POST['ddelivery_order_id'])){

        if ($obj->PHPShopCart->getNum() > 0) {
            if (PHPShopSecurity::true_param($_POST['mail'], $_POST['name_person'], $_POST['tel_name'], $_POST['adr_name'])) {
                $obj->ouid = $_POST['ouid'];

                $order_metod = PHPShopSecurity::TotalClean($_POST['order_metod'], 1);
                $PHPShopOrm = new PHPShopOrm($obj->getValue('base.payment_systems'));

                $row = $PHPShopOrm->select(array('path'), array('id' => '=' . $order_metod, 'enabled' => "='1'"), false, array('limit' => 1));

                $path = $row['path'];

                // Поддержка старого API
                $LoadItems['System'] = $obj->PHPShopSystem->getArray();

                $obj->sum = $obj->PHPShopCart->getSum(false);
                $obj->num = $obj->PHPShopCart->getNum();
                $obj->weight = $obj->PHPShopCart->getWeight();

                // Валюта
                $obj->currency = $obj->PHPShopOrder->default_valuta_code;


                $id =  (int) $_POST['ddelivery_order_id'];

                try{
                    $IntegratorShop = new IntegratorShop();
                    $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
                    $order = $ddeliveryUI->initOrder($id);
                    $obj->delivery = $ddeliveryUI->getOrderClientDeliveryPrice( $order );
                    $obj->total = $obj->PHPShopOrder->returnSumma($obj->sum, $obj->discount) + $obj->delivery;
                }catch(\DDeliveryException $e){
                    $ddeliveryUI->logMessage($e);
                }
                // Стоимость доставки

                // Сообщения на e-mail
                $obj->mail();
                // Перехат модуля в середине функции

                if (file_exists("./payment/$path/order.php"))
                    include_once("./payment/$path/order.php");
                elseif ($order_metod < 1000)
                    exit("Нет файла ./payment/$path/order.php");


                // Данные от способа оплаты
                if (!empty($disp))
                    $obj->set('orderMesage', $disp);

                // Запись заказа в БД
                $obj->write();

                // SMS администратору
                $obj->sms();

                // Обнуление элемента корзины
                $PHPShopCartElement = new PHPShopCartElement(true);
                $PHPShopCartElement->init('miniCart');
            }
            else {

                $obj->set('mesageText', $obj->message($obj->lang('bad_order_mesage_1'), $obj->lang('bad_order_mesage_2')));

                // Подключаем шаблон
                $disp = ParseTemplateReturn($obj->getValue('templates.order_forma_mesage'));
                $disp.=PHPShopText::notice(PHPShopText::a('javascript:history.back(1)', $obj->lang('order_return')), 'images/shop/icon-setup.gif');
                $obj->set('orderMesage', $disp);
            }
        } else {

            $obj->set('mesageText', $obj->message($obj->lang('bad_cart_1'), $obj->lang('bad_order_mesage_2')));
            $disp = ParseTemplateReturn($obj->getValue('templates.order_forma_mesage'));
            $obj->set('orderMesage', $disp);
        }

        // Подключаем шаблон
        $obj->parseTemplate($obj->getValue('templates.order_forma_mesage_main'));
        return true;

    }

}
$addHandler=array
(
        'mail'=>'mail_ddelivery_hook',
        'send_to_order' => 'send_to_order_ddelivery_hook',
        'write' => 'write_ddelivery_hook'
);

?>