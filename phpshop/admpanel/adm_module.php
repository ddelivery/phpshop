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
    if( $_POST['zabor_new'] != '1' )
    {
        $_POST['zabor_new'] = 0;
    }
    $action = $PHPShopOrm->update($_POST);
    return $action;
}

function _prepareSelect( $val, $arrVals )
{
    for( $i = 0; $i < count($arrVals);$i++ )
    {

        if( $arrVals[$i][1] == $val )
        {
            $arrVals[$i][] = 'selected';
        }
        else
        {
            $arrVals[$i][] = '';
        }
    }
    return $arrVals;
}

function actionStart() {
    global $PHPShopGUI,$PHPShopSystem,$SysValue,$_classPath,$PHPShopOrm;

    $PHPShopGUI->dir=$_classPath."admpanel/";
    $PHPShopGUI->title="Настройки";
    $PHPShopGUI->size="750,750";

    // Выборка
    $data = $PHPShopOrm->select();
    @extract($data);




    $type_value[]=array('ПВЗ и Курьеры','0');
    $type_value[]=array('ПВЗ','1');
    $type_value[]=array('Курьеры','2');

    $type_value = _prepareSelect($type, $type_value);


    $rezhim_value[]=array('Тестирование (stage.ddelivery.ru)','0');
    $rezhim_value[]=array('Продакшн (cabinet.ddelivery.ru)','1');
    $rezhim_value = _prepareSelect($rezhim, $rezhim_value);

    // Графический заголовок окна
    $PHPShopGUI->setHeader("Настройки модуля 'DD'","настройки поключения",$PHPShopGUI->dir."img/i_display_settings_med[1].gif");


    $Tab1 = $PHPShopGUI->setField('API ключ(из личного кабинета)',
                                  $PHPShopGUI->setInputText(false,'api_new', $api,300));

    $Tab1 .= $PHPShopGUI->setField('ID способа доставки DDlivery',
        $PHPShopGUI->setInputText(false,'delivery_id_new', $delivery_id,300));

    $Tab1.=$PHPShopGUI->setField('Режим работы',$PHPShopGUI->setSelect('rezhim_new',$rezhim_value,400));

    $Tab1.=$PHPShopGUI->setField('Какой % от стоимости товара страхуется',
                                $PHPShopGUI->setInputText(false,'declared_new', $declared,300));
    $Tab1.= $PHPShopGUI->setText('<b>Соответствие полей</b>', 'none');

    $Tab1.=$PHPShopGUI->setField('Ширина',
                                $PHPShopGUI->setInputText(false,'width_new', $width,300));

    $Tab1.=$PHPShopGUI->setField('Длина',
        $PHPShopGUI->setInputText(false,'length_new', $length,300));

    $Tab1.=$PHPShopGUI->setField('Высота',
        $PHPShopGUI->setInputText(false,'height_new', $height,300));

    $Tab1.=$PHPShopGUI->setField('Вес',
        $PHPShopGUI->setInputText(false,'weight_new', $weight,300));


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
    if( count($status_base) )
    {
        foreach($status_base as $item){
            if( $item['id'] == $status)
            {
               $s = 'selected';
            }
            else
            {
               $s = '';
            }
            $status_value[] = array($item['name'], $item['id'], $s);
        }
    }

    $Tab5 = $PHPShopGUI->setField('Оплата на месте',$PHPShopGUI->setSelect('payment_new',$payment_value,400));
    $Tab5 .= $PHPShopGUI->setField('Статус для отправки',$PHPShopGUI->setSelect('status_new',$status_value,400));
    $Tab5 .= $PHPShopGUI->setField('Фамилия',
                $PHPShopGUI->setInputText(false,'famile_new', $famile,300));
    $Tab5 .= $PHPShopGUI->setField('Имя',
        $PHPShopGUI->setInputText(false,'name_new', $name,300));
    $Tab5.= $PHPShopGUI->setText('<b>Габариты по умолчанию</b>', 'none');

    $Tab5 .= $PHPShopGUI->setField('Ширина, см',
        $PHPShopGUI->setInputText(false,'def_width_new', $def_width,300));

    $Tab5 .= $PHPShopGUI->setField('Длина, см',
        $PHPShopGUI->setInputText(false,'def_lenght_new', $def_lenght,300));
    $Tab5 .= $PHPShopGUI->setField('Высота, см',
        $PHPShopGUI->setInputText(false,'def_height_new', $def_height,300));
    $Tab5 .= $PHPShopGUI->setField('Вес, кг',
        $PHPShopGUI->setInputText(false,'def_weight_new', $def_weight,300));

    $Tab2 =$PHPShopGUI->setField('Доступные способы',$PHPShopGUI->setSelect('type_new',$type_value,400));
    $Tab2.= $PHPShopGUI->setText('<b>Доступные компании ПВЗ</b>', 'none');

    $pvz_companies = unserialize( $pvz_companies );
    $cur_companies = unserialize( $cur_companies );

    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',1,'DPD',(in_array(1,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',2,'IML',(in_array(2,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',3,'Hermes-dpd',(in_array(3,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',4,'Logibox',(in_array(4,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',5,'Pickpoint',(in_array(5,$pvz_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setText('<b>Доступные компании курьерская доставка</b>', 'none');
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',1,'DPD',(in_array(1,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',2,'IML',(in_array(2,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',3,'СДЭК',(in_array(3,$cur_companies)?'checked':''));


    $Tab3 = $PHPShopGUI->setField('от',
                        $PHPShopGUI->setInputText(false,'from1_new', $from1,100 ),'left');

    $Tab3 .= $PHPShopGUI->setField('до',
        $PHPShopGUI->setInputText(false,'to1_new', $to1,100),'left');

    $method1_value[] = array('Клиент оплачивает все','1');
    $method1_value[] = array('Магазин оплачивает все','2');
    $method1_value[] = array('Магазин оплачивает процент от стоимости доставки','3');
    $method1_value[] = array('Магазин оплачивает конкретную сумму от доставки. Если сумма больше, то всю доставку','4');


    $method1_value = _prepareSelect($method1, $method1_value);
    $Tab3 .=$PHPShopGUI->setField('стоимость',$PHPShopGUI->setSelect('method1_new',$method1_value,150),'left');
    $Tab3 .= $PHPShopGUI->setField('Сума',
        $PHPShopGUI->setInputText(false,'methodval1_new', $methodval1, 100),'none');

    $Tab3 .= $PHPShopGUI->setField('от',
        $PHPShopGUI->setInputText(false,'from2_new', $from2,100),'left');

    $Tab3 .= $PHPShopGUI->setField('до',
        $PHPShopGUI->setInputText(false,'to2_new', $to2,100),'left');

    $method2_value[] = array('Клиент оплачивает все','1');
    $method2_value[] = array('Магазин оплачивает все','2');
    $method2_value[] = array('Магазин оплачивает процент от стоимости доставки','3');
    $method2_value[] = array('Магазин оплачивает конкретную сумму от доставки. Если сумма больше, то всю доставку','4');

    $method2_value = _prepareSelect($method2, $method2_value);
    $Tab3 .=$PHPShopGUI->setField('стоимость',$PHPShopGUI->setSelect('method2_new',$method2_value,150),'left');
    $Tab3 .= $PHPShopGUI->setField('Сума',
        $PHPShopGUI->setInputText(false,'methodval2_new', $methodval2,100));


    $Tab3 .= $PHPShopGUI->setField('от',
        $PHPShopGUI->setInputText(false,'from3_new', $from3,100),'left');
    $Tab3 .= $PHPShopGUI->setField('до',
        $PHPShopGUI->setInputText(false,'to3_new', $to3,100),'left');


    $method3_value[] = array('Клиент оплачивает все','1');
    $method3_value[] = array('Магазин оплачивает все','2');
    $method3_value[] = array('Магазин оплачивает процент от стоимости доставки','3');
    $method3_value[] = array('Магазин оплачивает конкретную сумму от доставки. Если сумма больше, то всю доставку','4');


    $method3_value = _prepareSelect($method3, $method3_value);

    $Tab3 .=$PHPShopGUI->setField('стоимость',$PHPShopGUI->setSelect('method3_new',$method3_value,250),'left');
    $Tab3 .= $PHPShopGUI->setField('Сума',
        $PHPShopGUI->setInputText(false,'methodval3_new', $methodval3,100));


    $okrugl_value[] = array('Округлять в меньшую сторону','0');
    $okrugl_value[] = array('Округлять в большую сторону','1');
    $okrugl_value[] = array('Округлять цену в математически','2');

    $okrugl_value = _prepareSelect($okrugl, $okrugl_value);

    $Tab3 .=$PHPShopGUI->setField('Округление цены доставки для покупателя',$PHPShopGUI->setSelect('okrugl_new',$okrugl_value,150),'left');
    $Tab3.= $PHPShopGUI->setText('шаг', 'left');
    $Tab3 .= $PHPShopGUI->setField('руб',
        $PHPShopGUI->setInputText(false,'shag_new', $shag,100));

    $Tab3.= $PHPShopGUI->setCheckbox('zabor_new',1,'Выводить стоимость забора в цене доставки',(($zabor == '1')?'checked':''));

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
    // $_POST['pvz_companies'] = serialize('pvz_companies');
   // $_POST['cur_companies'] = serialize('cur_companies');
    //$Tab1=$PHPShopGUI->setField('Первое поле DD',$PHPShopGUI->setInputText(false,'city_new', $city,300,'<br>* Somme comment'));

    /*
    $Tab1=$PHPShopGUI->setField('Третье поле',$PHPShopGUI->setSelect('type_new', $type_value, 150));

    $Tab1.=$PHPShopGUI->setField('Второе поле',$PHPShopGUI->setInputText(false,'name_new', $name,300));


    $Tab1.=$PHPShopGUI->setField('Четвертое поле',$PHPShopGUI->setSelect('type_reception_new',$type_reception_value,400));

    */
    $info='some info about module';

    //$Tab2=$PHPShopGUI->setInfo($info, 200, '96%');

    // Форма регистрации
    //$Tab3=$PHPShopGUI->setPay($serial,false);

    // Вывод формы закладки
    $PHPShopGUI->setTab(array("Основные",$Tab1,480),array("Основные",$Tab5,470),array("Настройки способов доставки",$Tab2,270),
          array("Настройки цены доставки",$Tab3,270) /*, array("Добавление собственных служб доставки",$Tab4,320) */);

    // Вывод кнопок сохранить и выход в футер
    $ContentFooter=
        $PHPShopGUI->setInput("hidden","newsID",$id,"right",70,"","but").
        $PHPShopGUI->setInput("button","","Cancel","right",70,"return onCancel();","but").
        $PHPShopGUI->setInput("submit","editID","OK","right",70,"","but","actionUpdate");

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