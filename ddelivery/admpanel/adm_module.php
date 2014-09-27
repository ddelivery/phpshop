<?php

$_classPath="../../../";
include($_classPath."class/obj.class.php");
PHPShopObj::loadClass("base");
PHPShopObj::loadClass("system");
PHPShopObj::loadClass("security");
PHPShopObj::loadClass("orm");

$PHPShopBase = new PHPShopBase($_classPath."inc/config.ini");
include($_classPath."admpanel/enter_to_admin.php");


// Настройки модуля
PHPShopObj::loadClass("modules");
$PHPShopModules = new PHPShopModules($_classPath."modules/");


// Редактор
PHPShopObj::loadClass("admgui");
$PHPShopGUI = new PHPShopGUI();

// SQL
$PHPShopOrm = new PHPShopOrm($PHPShopModules->getParam("base.ddelivery.ddelivery_system"));


// Функция обновления
function actionUpdate() {
    global $PHPShopOrm;

    $PHPShopOrm->debug=false;
    $_POST['pvz_companies_new'] = serialize($_POST['pvz_companies_new']);
    $_POST['cur_companies_new'] = serialize($_POST['cur_companies_new']);


    $_POST['courier_list_new'] = serialize($_POST['courier_list_new']);
    $_POST['self_list_new'] = serialize($_POST['self_list_new']);

    if(! is_array( $_POST['self_way_new'] )){
        $_POST['self_way_new'] = array();
    }

    if(! is_array( $_POST['courier_way_new'] )){
        $_POST['courier_way_new'] = array();
    }

    $_POST['settings_new'] = json_encode( array('self_way' => $_POST['self_way_new'],
                                                'courier_way' => $_POST['courier_way_new'] ) );

    if( $_POST['zabor_new'] != '1' ){
        $_POST['zabor_new'] = 0;
    }
    $action = $PHPShopOrm->update($_POST);
    return $action;
}

/**
 * Экшен сохранения
 */
function actionSave() {
    global $PHPShopGUI;

    // Сохранение данных
    actionUpdate();

    $PHPShopGUI->setAction(1, 'actionStart', 'none');
}

function _prepareSelect( $val, $arrVals ){
    for( $i = 0; $i < count($arrVals);$i++ ){

        if( $arrVals[$i][1] == $val ){
            $arrVals[$i][] = 'selected';
        }else{
            $arrVals[$i][] = '';
        }
    }
    return $arrVals;
}

function actionStart() {
    global $PHPShopGUI,$PHPShopSystem,$SysValue,$_classPath,$PHPShopOrm;

    $PHPShopGUI->dir=$_classPath."admpanel/";
    $PHPShopGUI->title="Настройки";
    $PHPShopGUI->size="1550,750";

    // Выборка
    $data = $PHPShopOrm->select();
    @extract($data);


    $type_value[]=array('ПВЗ и Курьерская доставка','0');
    $type_value[]=array('ПВЗ','1');
    $type_value[]=array('Курьерская доставка','2');
    $type_value[]=array('Разделить ПВЗ и Курьерскую доставку','3');


    $type_value = _prepareSelect($type, $type_value);


    $rezhim_value[]=array('Тестирование (stage.ddelivery.ru)','0');
    $rezhim_value[]=array('Рабочий (cabinet.ddelivery.ru)','1');
    $rezhim_value = _prepareSelect($rezhim, $rezhim_value);

    // Графический заголовок окна
    $PHPShopGUI->setHeader("Настройки модуля 'DD'","настройки поключения",$PHPShopGUI->dir."img/i_display_settings_med[1].gif");

    $Tab1 = $PHPShopGUI->setText('<b>Ключ можно получить в личном кабинете
                                  DDelivery.ru, зарегистрировавшись на сайте ( для новых клиентов )</b>', 'none');
    $Tab1 .= $PHPShopGUI->setField('API ключ(из личного кабинета)',
                                  $PHPShopGUI->setInputText(false,'api_new', $api,300));


    if( !isset($settings) || empty( $settings) ){
        $settings = array('self_way' => array(), 'courier_way' => array());
    }else{
        $settings = json_decode($settings, true);
    }
    $self_way = $settings['self_way'];
    $courier_way = $settings['courier_way'];


    $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['delivery']);
    $data = $PHPShopOrm->select(array('id', 'city'), array('PID' => " = " . "0", 'enabled' => " = '" . "1'"), false /*, array('limit' => 1)*/);


    $courier_way_select = '<select name="courier_way_new[]" size="8" multiple>';
    $self_way_select =    '<select name="self_way_new[]" size="8" multiple>';

    if (is_array($data)){
        foreach( $data as $item ){

            if( in_array($item['id'], $courier_way) ){
                $selected_c = 'selected="selected"';
            }else{
                $selected_c = '';
            }

            if( in_array($item['id'], $self_way) ){
                $selected_s = 'selected="selected"';
            }else{
                $selected_s = '';
            }
            $courier_way_select .= '<option ' . $selected_c . ' value="' . $item['id'] . '">' . $item['city'] . '</option>';
            $self_way_select .= '<option ' . $selected_s . ' value="' . $item['id'] . '">' . $item['city'] . '</option>';
        }
    }
    $courier_way_select .= '</select>';
    $self_way_select .= '</select>';


    $Tab1 .= $PHPShopGUI->setField('Соответствие способа доставки DDelivery самовывоз', $self_way_select);
    $Tab1 .= $PHPShopGUI->setField('Соответствие способа доставки DDelivery курьер( Если способы доставки не разделены то не активна  )', $courier_way_select);



    $Tab1 .= $PHPShopGUI->setText('<b>Для отладки модуля используйте пожалуйста режим тестирования.</b>', 'none');
    $Tab1.=$PHPShopGUI->setField('Режим работы',$PHPShopGUI->setSelect('rezhim_new',$rezhim_value,400));

    $Tab1 .= $PHPShopGUI->setText('<b>Вы можете снизить оценочную стоимость для уменьшения стоимости
                                   доставки за счет снижения размеров страховки</b>', 'none');
    $Tab1.=$PHPShopGUI->setField('Какой % от стоимости товара страхуется',
                                $PHPShopGUI->setInputText(false,'declared_new', $declared,300));

    $objBase=$GLOBALS['SysValue']['base']['table_name48'];
    $PHPShopOrm2 = new PHPShopOrm($objBase);
    $payment_base = $PHPShopOrm2->select();
    if( count($payment_base) )
    {
        foreach($payment_base as $item){
            if($item['enabled'])
            {
                if( $item['id'] == $payment )
                {
                    $s = 'selected';
                }
                else
                {
                    $s = '';
                }
                $payment_value[] = array($item['name'], $item['id'], $s);
            }
        }
    }
    $objBase=$GLOBALS['SysValue']['base']['order_status'];
    $PHPShopOrm3 = new PHPShopOrm($objBase);
    $status_base = $PHPShopOrm3->select();
    if( count($status_base) ){
        foreach($status_base as $item){
            if( $item['id'] == $status){
               $s = 'selected';
            }else{
               $s = '';
            }
            $status_value[] = array($item['name'], $item['id'], $s);
        }
    }


    $Tab5  = $PHPShopGUI->setText('<b>Выберите поле соответствующее способу оплаты "оплата на месте".
                                      Например "оплата курьеру". У вас в системе может быть только 1 такой способ</b>', 'none');
    $Tab5 .= $PHPShopGUI->setField('Оплата на месте',$PHPShopGUI->setSelect('payment_new',$payment_value,400));
    //print_r($pvz_companies);
    $self_list = unserialize( $self_list );
    $courier_list = unserialize( $courier_list );
        $payment_courier = '<select name="courier_list_new[]" size="8" multiple>';
        $payment_self =    '<select name="self_list_new[]" size="8" multiple>';
                            foreach( $payment_value as $item ){
                                if( in_array($item[1], $courier_list) ){
                                    $selected_c = 'selected="selected"';
                                }else{
                                    $selected_c = '';
                                }
                                if( in_array($item[1], $self_list) ){
                                    $selected_s = 'selected="selected"';
                                }else{
                                    $selected_s = '';
                                }
                                $payment_courier .= '<option ' . $selected_c . ' value="' . $item[1] . '">' . $item[0] . '</option>';
                                $payment_self .= '<option ' . $selected_s . ' value="' . $item[1] . '">' . $item[0] . '</option>';
                            }
        $payment_courier .= '</select>';
        $payment_self .= '</select>';

    $Tab5 .= $PHPShopGUI->setField('Доступные способы оплаты для курьерской доставки', $payment_courier);
    $Tab5 .= $PHPShopGUI->setField('Доступные способы оплаты для самовывоза', $payment_self);



    $Tab5 .= $PHPShopGUI->setText('<b>Выберите статус при котором заявки из вашей системы будут уходить в DDelivery.
                                      Помните что отправка означает готовность отгрузить заказ на следующий рабочий день</b>', 'none');
    $Tab5 .= $PHPShopGUI->setField('Статус для отправки',$PHPShopGUI->setSelect('status_new',$status_value,400));


    $Tab5.= $PHPShopGUI->setText('<b>Габариты по умолчанию</b>', 'none');
    $Tab5 .= $PHPShopGUI->setText('<b>Данные габариты используются для определения цены доставки в случае, если у
                                      товара не прописаны размеры. Просим внимательней отнестись к ввод данных полей</b>', 'none');
    $Tab5 .= $PHPShopGUI->setField('Ширина, см',
        $PHPShopGUI->setInputText(false,'def_width_new', $def_width,300));

    $Tab5 .= $PHPShopGUI->setField('Длина, см',
        $PHPShopGUI->setInputText(false,'def_lenght_new', $def_lenght,300));
    $Tab5 .= $PHPShopGUI->setField('Высота, см',
        $PHPShopGUI->setInputText(false,'def_height_new', $def_height,300));
    $Tab5 .= $PHPShopGUI->setField('Вес, кг',
        $PHPShopGUI->setInputText(false,'def_weight_new', $def_weight,300));

    $Tab2 =  $PHPShopGUI->setText('<b>Настройка влияет на то, какие методы будут отображатся</b>', 'none');
    $Tab2 .= $PHPShopGUI->setField('Доступные способы',$PHPShopGUI->setSelect('type_new',$type_value,400));
    $Tab2 .= $PHPShopGUI->setText('<b>Выберите компании ПВЗ, которые вы бы хотели сделать доступными для ваших клиентов</b>', 'none');

    $pvz_companies = unserialize( $pvz_companies );
    $cur_companies = unserialize( $cur_companies );


    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',4,'Boxberry',(in_array(4,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',21,'Boxberry Express',(in_array(21,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',29,'DPD Classic',(in_array(29,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',23,'DPD Consumer',(in_array(23,$pvz_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',27,'DPD ECONOMY',(in_array(27,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',28,'DPD Express',(in_array(28,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',20,'DPD Parcel',(in_array(20,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',30,'EMS',(in_array(30,$pvz_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',31,'Grastin',(in_array(31,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',11,'Hermes',(in_array(11,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',16,'IM Logistics Пушкинская',(in_array(16,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',22,'IM Logistics Экспресс',(in_array(22,$pvz_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',17,'IMLogistics',(in_array(17,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',3,'Logibox',(in_array(3,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',14,'Maxima Express',(in_array(14,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',1,'PickPoint',(in_array(1,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',13,'КТС',(in_array(13,$pvz_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',18,'Сам Заберу',(in_array(18,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',6,'СДЭК забор',(in_array(6,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',26,'СДЭК Посылка',(in_array(26,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',25,' СДЭК Посылка Самовывоз',(in_array(25,$pvz_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',24,'Сити Курьер',(in_array(24,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',7,'QIWI Post',(in_array(7,$pvz_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',35,'Aplix DPD Consumer',(in_array(35,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',36,'Aplix DPD parcel',(in_array(36,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',37,'Aplix IML самовывоз',(in_array(37,$pvz_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',38,'Aplix PickPoint',(in_array(38,$pvz_companies)?'checked':''));


    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',39,'Aplix Qiwi',(in_array(39,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',40,'Aplix СДЭК',(in_array(40,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',42,'IML самовывоз',(in_array(42,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',43,'IML курьерская доставка',(in_array(43,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',44,'Почта России',(in_array(44,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',45,'Aplix курьерская доставка',(in_array(45,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',46,'LENOD курьерская служба',(in_array(46,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',47,'TelePost',(in_array(47,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',48,'Aplix IML курьерская доставка',(in_array(48,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',49,'IML Забор',(in_array(49,$pvz_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setText('<b>Выберите компании курьерской доставки, которые вы бы хотели сделать доступными для ваших клиентов</b>', 'none');
     $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',4,'Boxberry',(in_array(4,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',21,'Boxberry Express',(in_array(21,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',29,'DPD Classic',(in_array(29,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',23,'DPD Consumer',(in_array(23,$cur_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',27,'DPD ECONOMY',(in_array(27,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',28,'DPD Express',(in_array(28,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',20,'DPD Parcel',(in_array(20,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',30,'EMS',(in_array(30,$cur_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',31,'Grastin',(in_array(31,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',11,'Hermes',(in_array(11,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',16,'IM Logistics Пушкинская',(in_array(16,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',22,'IM Logistics Экспресс',(in_array(22,$cur_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',17,'IMLogistics',(in_array(17,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',3,'Logibox',(in_array(3,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',14,'Maxima Express',(in_array(14,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',1,'PickPoint',(in_array(1,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',13,'КТС',(in_array(13,$cur_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',18,'Сам Заберу',(in_array(18,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',6,'СДЭК забор',(in_array(6,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',26,'СДЭК Посылка',(in_array(26,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',25,' СДЭК Посылка Самовывоз',(in_array(25,$cur_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',24,'Сити Курьер',(in_array(24,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',7,'QIWI Post',(in_array(7,$cur_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',35,'Aplix DPD Consumer',(in_array(35,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',36,'Aplix DPD parcel',(in_array(36,$cur_companies)?'checked':''));


    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',38,'Aplix PickPoint',(in_array(38,$cur_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',37,'Aplix IML самовывоз',(in_array(37,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',39,'Aplix Qiwi',(in_array(39,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',40,'Aplix СДЭК',(in_array(40,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',42,'IML самовывоз',(in_array(42,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',43,'IML курьерская доставка',(in_array(43,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',44,'Почта России',(in_array(44,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',45,'Aplix курьерская доставка',(in_array(45,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',46,'LENOD курьерская служба',(in_array(46,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',47,'TelePost',(in_array(47,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',48,'Aplix IML курьерская доставка',(in_array(48,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',49,'IML Забор',(in_array(49,$cur_companies)?'checked':''));

    $Tab3  =  $PHPShopGUI->setText('<b>Как меняется стоимость доставки в зависимости от размера заказа в руб.
                                       Вы можете гибко настроить условия доставки,чтобы учесть вашу
                                       маркетинговую политику.</b>', 'none');
    $Tab3 .= $PHPShopGUI->setField('от',
                        $PHPShopGUI->setInputText(false,'from1_new', $from1,100 ),'left');

    $Tab3 .= $PHPShopGUI->setField('до',
        $PHPShopGUI->setInputText(false,'to1_new', $to1,100),'left');

    $method1_value[] = array('Клиент оплачивает все','1');
    $method1_value[] = array('Магазин оплачивает все','2');
    $method1_value[] = array('Магазин оплачивает процент от стоимости доставки','3');
    $method1_value[] = array('Магазин оплачивает конкретную сумму от доставки. Если сумма больше, то всю доставку','4');


    $method1_value = _prepareSelect($method1, $method1_value);
    $Tab3 .=$PHPShopGUI->setField('Действие',$PHPShopGUI->setSelect('method1_new',$method1_value,150),'left');
    $Tab3 .= $PHPShopGUI->setField('Сумма',
        $PHPShopGUI->setInputText(false,'methodval1_new', $methodval1, 100),'none');

    $Tab3 .= $PHPShopGUI->setField('от',
        $PHPShopGUI->setInputText(false,'from2_new', $from2,100),'left');

    $Tab3 .= $PHPShopGUI->setField('до',
        $PHPShopGUI->setInputText(false,'to2_new', $to2,100),'left');

    $method2_value[] = array('Клиент оплачивает все','1');
    $method2_value[] = array('Магазин оплачивает все','2');
    $method2_value[] = array('Магазин оплачивает процент от стоимости доставки','3');
    $method2_value[] = array('Магазин оплачивает доставку в рамках указанной суммы','4');

    $method2_value = _prepareSelect($method2, $method2_value);
    $Tab3 .=$PHPShopGUI->setField('Действие',$PHPShopGUI->setSelect('method2_new',$method2_value,150),'left');
    $Tab3 .= $PHPShopGUI->setField('Сумма',
        $PHPShopGUI->setInputText(false,'methodval2_new', $methodval2,100));


    $Tab3 .= $PHPShopGUI->setField('от',
        $PHPShopGUI->setInputText(false,'from3_new', $from3,100),'left');
    $Tab3 .= $PHPShopGUI->setField('до',
        $PHPShopGUI->setInputText(false,'to3_new', $to3,100),'left');


    $method3_value[] = array('Клиент оплачивает все','1');
    $method3_value[] = array('Магазин оплачивает все','2');
    $method3_value[] = array('Магазин оплачивает процент от стоимости доставки','3');
    $method3_value[] = array('Магазин оплачивает доставку в рамках указанной суммы','4');


    $method3_value = _prepareSelect($method3, $method3_value);

    $Tab3 .=$PHPShopGUI->setField('Действие',$PHPShopGUI->setSelect('method3_new',$method3_value,150),'left');
    $Tab3 .= $PHPShopGUI->setField('Сумма',
        $PHPShopGUI->setInputText(false,'methodval3_new', $methodval3,100));


    $okrugl_value[] = array('Округлять в меньшую сторону','0');
    $okrugl_value[] = array('Округлять в большую сторону','1');
    $okrugl_value[] = array('Округлять цену  математически','2');

    $okrugl_value = _prepareSelect($okrugl, $okrugl_value);

    $Tab3 .=$PHPShopGUI->setField('Округление цены доставки для покупателя',$PHPShopGUI->setSelect('okrugl_new',$okrugl_value,150),'left');
    $Tab3.= $PHPShopGUI->setText('шаг', 'left');
    $Tab3 .= $PHPShopGUI->setField('руб',
        $PHPShopGUI->setInputText(false,'shag_new', $shag,100));

    $Tab3 .=  $PHPShopGUI->setText('<b>В некоторых случаях есть необходимость включить цену забора</b>', 'none');
    $Tab3 .= $PHPShopGUI->setCheckbox('zabor_new',1,'Выводить стоимость забора в цене доставки',(($zabor == '1')?'checked':''));

    /*

    $Tab3 .= $PHPShopGUI->setField('от',
        $PHPShopGUI->setInputText(false,'from3_new', $from3,100));
    $Tab3 .= $PHPShopGUI->setField('до',
        $PHPShopGUI->setInputText(false,'to3_new', $to3,100));


    $method3_value[] = array('Магазин оплачивает %','0');
    $Tab3 .=$PHPShopGUI->setField('',$PHPShopGUI->setSelect('method3_new',$method3_value,100));
    $Tab3 .= $PHPShopGUI->setField('',
        $PHPShopGUI->setInputText(false,'methodval3_new', $methodval3,100));
    */

    $Tab4 = $PHPShopGUI->setText('<b>Курьерская доставка</b>', 'none');
    //$Tab4 .=$PHPShopGUI->setField('',$PHPShopGUI->setSelect('city1_new',$method3_value,100));
    $Tab4.=$PHPShopGUI->setField('Введите город',
                                 $PHPShopGUI->setInputText(false,'city1_new', $city1,300,''), 'left');
    $Tab4.=$PHPShopGUI->setField('Цена доставки',
        $PHPShopGUI->setInputText(false,'curprice1_new', $curprice1,300,''), 'left');


    $Tab4.=$PHPShopGUI->setField('Введите город',
        $PHPShopGUI->setInputText(false,'city2_new', $city2,300,''), 'left');
    $Tab4.=$PHPShopGUI->setField('Цена доставки',
        $PHPShopGUI->setInputText(false,'curprice2_new', $curprice2,300,''), 'left');


    $Tab4.=$PHPShopGUI->setField('Введите город',
        $PHPShopGUI->setInputText(false,'city3_new', $city3,300,''), 'left');
    $Tab4.=$PHPShopGUI->setField('Цена доставки',
        $PHPShopGUI->setInputText(false,'curprice3_new', $curprice3,300,''));

    $Tab4.= $PHPShopGUI->setText('<b>ПВЗ</b>', 'none');
    $Tab4.=$PHPShopGUI->setField('',
        $PHPShopGUI->setTextarea('custom_point_new',$custom_point));



    $info='Уважаемые пользователи! Мы постарались сделать настройки наиболее гибкими,
           но от вас требуется внимательность при выборе параметров. Если Вам непонятно
           значение каких-то настроек, просим связатся с менеджерами DD. В случае, если
           Вам потребуется больше настроек, так же просим связатся с клиентским отделом info@ddelivery.ru, Skype - ddelivery .
           ';

    $Tab7 = $PHPShopGUI->setInfo($info, 200, '96%');



    // Вывод формы закладки
    $PHPShopGUI->setTab(array("Основные",$Tab1,500),array("Дополнительные",$Tab5,740),array("Настройки способов доставки",$Tab2,520),
          array("Настройки цены доставки",$Tab3,300), array("Описание",$Tab7, 320) /*, array("Добавление собственных служб доставки",$Tab4,320) */);

    // Вывод кнопок сохранить и выход в футер
    $ContentFooter=
        $PHPShopGUI->setInput("hidden","newsID",$id,"right",70,"","but").
        $PHPShopGUI->setInput("button","","Cancel","right",70,"return onCancel();","but").
        $PHPShopGUI->setInput("submit","editID","OK","right",70,"","but","actionUpdate");
    //$PHPShopGUI->setInput("submit", "saveID", "Применить", "right", 80, "", "but", "actionUpdate");
    $PHPShopGUI->setFooter($ContentFooter);
    return true;
}

if($UserChek->statusPHPSHOP < 2) {

    // Вывод формы при старте
    $PHPShopGUI->setLoader($_POST['editID'],'actionStart');

    // Обработка событий
    $PHPShopGUI->getAction();

}else $UserChek->BadUserFormaWindow();

?>