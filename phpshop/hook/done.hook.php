<?php

include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/application/bootstrap.php');
include_once( $_SERVER['DOCUMENT_ROOT'] .  '/phpshop/modules/ddelivery/class/mrozk/IntegratorShop.php' );
/**
 * E-mail XML файла заказа
 */
/*
function mail_ddelivery_hook($obj,$row,$rout) {

    if($rout == 'END' and !empty($_POST['pickpoint_id'])){
        
        $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['pickpoint']['pickpoint_system']);
        $option=$PHPShopOrm->select();
        

        $content_file='<?xml version="1.0" encoding="windows-1251"?>
<documents>
<document>
<fio>'.$_POST['name_person'].'</fio>
<sms_phone>+7'.$_POST['tel_code'].$_POST['tel_name'].'</sms_phone>
<email>'.$_POST['mail'].'</email>
<additional_phones/>
<order_id>'.$_POST['ouid'].'</order_id>
<summ_rub>'.$obj->get('total').'</summ_rub>
<terminal_id>'.$_POST['pickpoint_id'].'</terminal_id>
<type_service>'.$option['type_service'].'</type_service>
<type_reception>'.$option['type_reception'].'</type_reception>
<embed>'.$obj->PHPShopSystem->getName().'</embed>
<size_x/> 
<size_y/> 
<size_z/> 
</document>
</documents>';

        // Запись в файл
        $file='files/price/'.$_POST['ouid'].'.xml';
        @fwrite(fopen($file,"w+"), $content_file);
        
        // Отсылаем письмо администратору
        $PHPShopMailFile= new PHPShopMailFile($obj->PHPShopSystem->getParam('adminmail2'),$_POST['mail'],'PickPoint N'.$_POST['ouid'],$content,'PickPoint_'.$_POST['ouid'].'.xml',$file);

        // Удаляем файл
        unlink($file);
    }

}
*/
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

        // Запись заказа в БД
        $result = $obj->PHPShopOrm->insert($insert);
        $cmsID =  mysql_insert_id();


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

            try
            {

                $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
                $ddeliveryUI->onCmsOrderFinish($id, $cmsID, 0, 3);
            }
            catch(\DDelivery\DDeliveryException $e)
            {
                exit( 'asdasdasdasdassadasd' );
            }

        }

        // Проверка ошибок при записи заказа
        $obj->error_report($result, array("Cart" => $cart, "Person" => $person, 'insert' => $insert));

        // Принудительная очистка корзины
        $obj->PHPShopCart->clean();
        return true;
       // exit("ads");
    /*
        // Данные для записи
        $insert = $_POST;
        $insert['datas_new'] = time();
        $insert['uid_new'] = $obj->ouid;
        $insert['orders_new'] = $obj->order;
        $insert['status_new'] = serialize($obj->status);
        $insert['user_new'] = $_SESSION['UsersId'];

        // Запись заказа в БД
        $result = $obj->PHPShopOrm->insert($insert);
        if(!empty($_POST['ddelivery_order_id']))
        {
            $id =  (int) $_POST['ddelivery_order_id'];
            $IntegratorShop = new IntegratorShop();
            $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
            $cmsID =  mysql_insert_id();
            print_r($_POST);
            $ddeliveryUI->onCmsOrderFinish($id, $cmsID, 0, 3);
        }


        // Проверка ошибок при записи заказа
       // $obj->error_report($result, array("Cart" => $cart, "Person" => $person, 'insert' => $insert));

        // Принудительная очистка корзины
        $this->PHPShopCart->clean();
        return true;
   */
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
                $IntegratorShop = new IntegratorShop();
                $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
                $obj->delivery = $ddeliveryUI->getDeliveryPrice($id);
                $obj->total = $obj->PHPShopOrder->returnSumma($obj->sum, $obj->discount) + $obj->delivery;

                // Стоимость доставки
                //$obj->delivery = $ddeliveryUI->getDeliveryPrice($id);//$this->PHPShopDelivery->getPrice($this->PHPShopCart->getSum(false), $this->PHPShopCart->getWeight());

                // Скидка
                //$obj->discount = $obj->PHPShopOrder->ChekDiscount($obj->PHPShopCart->getSum());

                // Итого
                //$obj->total = $this->PHPShopOrder->returnSumma($this->sum, $this->discount) + $this->delivery;

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

            $obj->set('mesageText', $obj->message($this->lang('bad_cart_1'), $obj->lang('bad_order_mesage_2')));
            $disp = ParseTemplateReturn($obj->getValue('templates.order_forma_mesage'));
            $obj->set('orderMesage', $disp);
        }

        // Подключаем шаблон
        $obj->parseTemplate($obj->getValue('templates.order_forma_mesage_main'));
        return true;
        /*
        $id =  (int) $_POST['ddelivery_order_id'];
        $IntegratorShop = new IntegratorShop();
        $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
        $obj->delivery = $ddeliveryUI->getDeliveryPrice($id);
        $obj->total = $obj->PHPShopOrder->returnSumma($obj->sum, $obj->discount) + $obj->delivery;
        */
        //print_r( $obj->PHPShopOrder );
        //$ddeliveryUI->onCmsOrderFinish( $id,  )
        //print_r($obj->PHPShopOrder);
        //echo $obj->total;
        //$ddeliveryUI->onCmsOrderFinish((int)$_POST['ddelivery_order_id'], )
        //echo $_POST['ddelivery_order_id'];
        //print_r($IntegratorShop->getProductsFromCart());

        /*

        echo 'hello';
        $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);
        //$ddeliveryUI->onCmsOrderFinish();
        //print_r($obj);

        */
        // exit('asd');
    }

}
$addHandler=array
(
        //'mail'=>'mail_pickpoint_hook',
        'send_to_order' => 'send_to_order_ddelivery_hook',
        'write' => 'write_ddelivery_hook'
);

?>