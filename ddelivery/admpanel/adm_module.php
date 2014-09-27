<?php

$_classPath="../../../";
include($_classPath."class/obj.class.php");
PHPShopObj::loadClass("base");
PHPShopObj::loadClass("system");
PHPShopObj::loadClass("security");
PHPShopObj::loadClass("orm");

$PHPShopBase = new PHPShopBase($_classPath."inc/config.ini");
include($_classPath."admpanel/enter_to_admin.php");


// ��������� ������
PHPShopObj::loadClass("modules");
$PHPShopModules = new PHPShopModules($_classPath."modules/");


// ��������
PHPShopObj::loadClass("admgui");
$PHPShopGUI = new PHPShopGUI();

// SQL
$PHPShopOrm = new PHPShopOrm($PHPShopModules->getParam("base.ddelivery.ddelivery_system"));


// ������� ����������
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
 * ����� ����������
 */
function actionSave() {
    global $PHPShopGUI;

    // ���������� ������
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
    $PHPShopGUI->title="���������";
    $PHPShopGUI->size="1550,750";

    // �������
    $data = $PHPShopOrm->select();
    @extract($data);


    $type_value[]=array('��� � ���������� ��������','0');
    $type_value[]=array('���','1');
    $type_value[]=array('���������� ��������','2');
    $type_value[]=array('��������� ��� � ���������� ��������','3');


    $type_value = _prepareSelect($type, $type_value);


    $rezhim_value[]=array('������������ (stage.ddelivery.ru)','0');
    $rezhim_value[]=array('������� (cabinet.ddelivery.ru)','1');
    $rezhim_value = _prepareSelect($rezhim, $rezhim_value);

    // ����������� ��������� ����
    $PHPShopGUI->setHeader("��������� ������ 'DD'","��������� ����������",$PHPShopGUI->dir."img/i_display_settings_med[1].gif");

    $Tab1 = $PHPShopGUI->setText('<b>���� ����� �������� � ������ ��������
                                  DDelivery.ru, ������������������� �� ����� ( ��� ����� �������� )</b>', 'none');
    $Tab1 .= $PHPShopGUI->setField('API ����(�� ������� ��������)',
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


    $Tab1 .= $PHPShopGUI->setField('������������ ������� �������� DDelivery ���������', $self_way_select);
    $Tab1 .= $PHPShopGUI->setField('������������ ������� �������� DDelivery ������( ���� ������� �������� �� ��������� �� �� �������  )', $courier_way_select);



    $Tab1 .= $PHPShopGUI->setText('<b>��� ������� ������ ����������� ���������� ����� ������������.</b>', 'none');
    $Tab1.=$PHPShopGUI->setField('����� ������',$PHPShopGUI->setSelect('rezhim_new',$rezhim_value,400));

    $Tab1 .= $PHPShopGUI->setText('<b>�� ������ ������� ��������� ��������� ��� ���������� ���������
                                   �������� �� ���� �������� �������� ���������</b>', 'none');
    $Tab1.=$PHPShopGUI->setField('����� % �� ��������� ������ ����������',
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


    $Tab5  = $PHPShopGUI->setText('<b>�������� ���� ��������������� ������� ������ "������ �� �����".
                                      �������� "������ �������". � ��� � ������� ����� ���� ������ 1 ����� ������</b>', 'none');
    $Tab5 .= $PHPShopGUI->setField('������ �� �����',$PHPShopGUI->setSelect('payment_new',$payment_value,400));
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

    $Tab5 .= $PHPShopGUI->setField('��������� ������� ������ ��� ���������� ��������', $payment_courier);
    $Tab5 .= $PHPShopGUI->setField('��������� ������� ������ ��� ����������', $payment_self);



    $Tab5 .= $PHPShopGUI->setText('<b>�������� ������ ��� ������� ������ �� ����� ������� ����� ������� � DDelivery.
                                      ������� ��� �������� �������� ���������� ��������� ����� �� ��������� ������� ����</b>', 'none');
    $Tab5 .= $PHPShopGUI->setField('������ ��� ��������',$PHPShopGUI->setSelect('status_new',$status_value,400));


    $Tab5.= $PHPShopGUI->setText('<b>�������� �� ���������</b>', 'none');
    $Tab5 .= $PHPShopGUI->setText('<b>������ �������� ������������ ��� ����������� ���� �������� � ������, ���� �
                                      ������ �� ��������� �������. ������ ������������ ��������� � ���� ������ �����</b>', 'none');
    $Tab5 .= $PHPShopGUI->setField('������, ��',
        $PHPShopGUI->setInputText(false,'def_width_new', $def_width,300));

    $Tab5 .= $PHPShopGUI->setField('�����, ��',
        $PHPShopGUI->setInputText(false,'def_lenght_new', $def_lenght,300));
    $Tab5 .= $PHPShopGUI->setField('������, ��',
        $PHPShopGUI->setInputText(false,'def_height_new', $def_height,300));
    $Tab5 .= $PHPShopGUI->setField('���, ��',
        $PHPShopGUI->setInputText(false,'def_weight_new', $def_weight,300));

    $Tab2 =  $PHPShopGUI->setText('<b>��������� ������ �� ��, ����� ������ ����� �����������</b>', 'none');
    $Tab2 .= $PHPShopGUI->setField('��������� �������',$PHPShopGUI->setSelect('type_new',$type_value,400));
    $Tab2 .= $PHPShopGUI->setText('<b>�������� �������� ���, ������� �� �� ������ ������� ���������� ��� ����� ��������</b>', 'none');

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
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',16,'IM Logistics ����������',(in_array(16,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',22,'IM Logistics ��������',(in_array(22,$pvz_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',17,'IMLogistics',(in_array(17,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',3,'Logibox',(in_array(3,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',14,'Maxima Express',(in_array(14,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',1,'PickPoint',(in_array(1,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',13,'���',(in_array(13,$pvz_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',18,'��� ������',(in_array(18,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',6,'���� �����',(in_array(6,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',26,'���� �������',(in_array(26,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',25,' ���� ������� ���������',(in_array(25,$pvz_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',24,'���� ������',(in_array(24,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',7,'QIWI Post',(in_array(7,$pvz_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',35,'Aplix DPD Consumer',(in_array(35,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',36,'Aplix DPD parcel',(in_array(36,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',37,'Aplix IML ���������',(in_array(37,$pvz_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',38,'Aplix PickPoint',(in_array(38,$pvz_companies)?'checked':''));


    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',39,'Aplix Qiwi',(in_array(39,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',40,'Aplix ����',(in_array(40,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',42,'IML ���������',(in_array(42,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',43,'IML ���������� ��������',(in_array(43,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',44,'����� ������',(in_array(44,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',45,'Aplix ���������� ��������',(in_array(45,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',46,'LENOD ���������� ������',(in_array(46,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',47,'TelePost',(in_array(47,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',48,'Aplix IML ���������� ��������',(in_array(48,$pvz_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('pvz_companies_new[]',49,'IML �����',(in_array(49,$pvz_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setText('<b>�������� �������� ���������� ��������, ������� �� �� ������ ������� ���������� ��� ����� ��������</b>', 'none');
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
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',16,'IM Logistics ����������',(in_array(16,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',22,'IM Logistics ��������',(in_array(22,$cur_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',17,'IMLogistics',(in_array(17,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',3,'Logibox',(in_array(3,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',14,'Maxima Express',(in_array(14,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',1,'PickPoint',(in_array(1,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',13,'���',(in_array(13,$cur_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',18,'��� ������',(in_array(18,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',6,'���� �����',(in_array(6,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',26,'���� �������',(in_array(26,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',25,' ���� ������� ���������',(in_array(25,$cur_companies)?'checked':''));
    $Tab2.= '<br />';
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',24,'���� ������',(in_array(24,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',7,'QIWI Post',(in_array(7,$cur_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',35,'Aplix DPD Consumer',(in_array(35,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',36,'Aplix DPD parcel',(in_array(36,$cur_companies)?'checked':''));


    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',38,'Aplix PickPoint',(in_array(38,$cur_companies)?'checked':''));

    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',37,'Aplix IML ���������',(in_array(37,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',39,'Aplix Qiwi',(in_array(39,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',40,'Aplix ����',(in_array(40,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',42,'IML ���������',(in_array(42,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',43,'IML ���������� ��������',(in_array(43,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',44,'����� ������',(in_array(44,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',45,'Aplix ���������� ��������',(in_array(45,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',46,'LENOD ���������� ������',(in_array(46,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',47,'TelePost',(in_array(47,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',48,'Aplix IML ���������� ��������',(in_array(48,$cur_companies)?'checked':''));
    $Tab2.= $PHPShopGUI->setCheckbox('cur_companies_new[]',49,'IML �����',(in_array(49,$cur_companies)?'checked':''));

    $Tab3  =  $PHPShopGUI->setText('<b>��� �������� ��������� �������� � ����������� �� ������� ������ � ���.
                                       �� ������ ����� ��������� ������� ��������,����� ������ ����
                                       ������������� ��������.</b>', 'none');
    $Tab3 .= $PHPShopGUI->setField('��',
                        $PHPShopGUI->setInputText(false,'from1_new', $from1,100 ),'left');

    $Tab3 .= $PHPShopGUI->setField('��',
        $PHPShopGUI->setInputText(false,'to1_new', $to1,100),'left');

    $method1_value[] = array('������ ���������� ���','1');
    $method1_value[] = array('������� ���������� ���','2');
    $method1_value[] = array('������� ���������� ������� �� ��������� ��������','3');
    $method1_value[] = array('������� ���������� ���������� ����� �� ��������. ���� ����� ������, �� ��� ��������','4');


    $method1_value = _prepareSelect($method1, $method1_value);
    $Tab3 .=$PHPShopGUI->setField('��������',$PHPShopGUI->setSelect('method1_new',$method1_value,150),'left');
    $Tab3 .= $PHPShopGUI->setField('�����',
        $PHPShopGUI->setInputText(false,'methodval1_new', $methodval1, 100),'none');

    $Tab3 .= $PHPShopGUI->setField('��',
        $PHPShopGUI->setInputText(false,'from2_new', $from2,100),'left');

    $Tab3 .= $PHPShopGUI->setField('��',
        $PHPShopGUI->setInputText(false,'to2_new', $to2,100),'left');

    $method2_value[] = array('������ ���������� ���','1');
    $method2_value[] = array('������� ���������� ���','2');
    $method2_value[] = array('������� ���������� ������� �� ��������� ��������','3');
    $method2_value[] = array('������� ���������� �������� � ������ ��������� �����','4');

    $method2_value = _prepareSelect($method2, $method2_value);
    $Tab3 .=$PHPShopGUI->setField('��������',$PHPShopGUI->setSelect('method2_new',$method2_value,150),'left');
    $Tab3 .= $PHPShopGUI->setField('�����',
        $PHPShopGUI->setInputText(false,'methodval2_new', $methodval2,100));


    $Tab3 .= $PHPShopGUI->setField('��',
        $PHPShopGUI->setInputText(false,'from3_new', $from3,100),'left');
    $Tab3 .= $PHPShopGUI->setField('��',
        $PHPShopGUI->setInputText(false,'to3_new', $to3,100),'left');


    $method3_value[] = array('������ ���������� ���','1');
    $method3_value[] = array('������� ���������� ���','2');
    $method3_value[] = array('������� ���������� ������� �� ��������� ��������','3');
    $method3_value[] = array('������� ���������� �������� � ������ ��������� �����','4');


    $method3_value = _prepareSelect($method3, $method3_value);

    $Tab3 .=$PHPShopGUI->setField('��������',$PHPShopGUI->setSelect('method3_new',$method3_value,150),'left');
    $Tab3 .= $PHPShopGUI->setField('�����',
        $PHPShopGUI->setInputText(false,'methodval3_new', $methodval3,100));


    $okrugl_value[] = array('��������� � ������� �������','0');
    $okrugl_value[] = array('��������� � ������� �������','1');
    $okrugl_value[] = array('��������� ����  �������������','2');

    $okrugl_value = _prepareSelect($okrugl, $okrugl_value);

    $Tab3 .=$PHPShopGUI->setField('���������� ���� �������� ��� ����������',$PHPShopGUI->setSelect('okrugl_new',$okrugl_value,150),'left');
    $Tab3.= $PHPShopGUI->setText('���', 'left');
    $Tab3 .= $PHPShopGUI->setField('���',
        $PHPShopGUI->setInputText(false,'shag_new', $shag,100));

    $Tab3 .=  $PHPShopGUI->setText('<b>� ��������� ������� ���� ������������� �������� ���� ������</b>', 'none');
    $Tab3 .= $PHPShopGUI->setCheckbox('zabor_new',1,'�������� ��������� ������ � ���� ��������',(($zabor == '1')?'checked':''));

    /*

    $Tab3 .= $PHPShopGUI->setField('��',
        $PHPShopGUI->setInputText(false,'from3_new', $from3,100));
    $Tab3 .= $PHPShopGUI->setField('��',
        $PHPShopGUI->setInputText(false,'to3_new', $to3,100));


    $method3_value[] = array('������� ���������� %','0');
    $Tab3 .=$PHPShopGUI->setField('',$PHPShopGUI->setSelect('method3_new',$method3_value,100));
    $Tab3 .= $PHPShopGUI->setField('',
        $PHPShopGUI->setInputText(false,'methodval3_new', $methodval3,100));
    */

    $Tab4 = $PHPShopGUI->setText('<b>���������� ��������</b>', 'none');
    //$Tab4 .=$PHPShopGUI->setField('',$PHPShopGUI->setSelect('city1_new',$method3_value,100));
    $Tab4.=$PHPShopGUI->setField('������� �����',
                                 $PHPShopGUI->setInputText(false,'city1_new', $city1,300,''), 'left');
    $Tab4.=$PHPShopGUI->setField('���� ��������',
        $PHPShopGUI->setInputText(false,'curprice1_new', $curprice1,300,''), 'left');


    $Tab4.=$PHPShopGUI->setField('������� �����',
        $PHPShopGUI->setInputText(false,'city2_new', $city2,300,''), 'left');
    $Tab4.=$PHPShopGUI->setField('���� ��������',
        $PHPShopGUI->setInputText(false,'curprice2_new', $curprice2,300,''), 'left');


    $Tab4.=$PHPShopGUI->setField('������� �����',
        $PHPShopGUI->setInputText(false,'city3_new', $city3,300,''), 'left');
    $Tab4.=$PHPShopGUI->setField('���� ��������',
        $PHPShopGUI->setInputText(false,'curprice3_new', $curprice3,300,''));

    $Tab4.= $PHPShopGUI->setText('<b>���</b>', 'none');
    $Tab4.=$PHPShopGUI->setField('',
        $PHPShopGUI->setTextarea('custom_point_new',$custom_point));



    $info='��������� ������������! �� ����������� ������� ��������� �������� �������,
           �� �� ��� ��������� �������������� ��� ������ ����������. ���� ��� ���������
           �������� �����-�� ��������, ������ �������� � ����������� DD. � ������, ����
           ��� ����������� ������ ��������, ��� �� ������ �������� � ���������� ������� info@ddelivery.ru, Skype - ddelivery .
           ';

    $Tab7 = $PHPShopGUI->setInfo($info, 200, '96%');



    // ����� ����� ��������
    $PHPShopGUI->setTab(array("��������",$Tab1,500),array("��������������",$Tab5,740),array("��������� �������� ��������",$Tab2,520),
          array("��������� ���� ��������",$Tab3,300), array("��������",$Tab7, 320) /*, array("���������� ����������� ����� ��������",$Tab4,320) */);

    // ����� ������ ��������� � ����� � �����
    $ContentFooter=
        $PHPShopGUI->setInput("hidden","newsID",$id,"right",70,"","but").
        $PHPShopGUI->setInput("button","","Cancel","right",70,"return onCancel();","but").
        $PHPShopGUI->setInput("submit","editID","OK","right",70,"","but","actionUpdate");
    //$PHPShopGUI->setInput("submit", "saveID", "���������", "right", 80, "", "but", "actionUpdate");
    $PHPShopGUI->setFooter($ContentFooter);
    return true;
}

if($UserChek->statusPHPSHOP < 2) {

    // ����� ����� ��� ������
    $PHPShopGUI->setLoader($_POST['editID'],'actionStart');

    // ��������� �������
    $PHPShopGUI->getAction();

}else $UserChek->BadUserFormaWindow();

?>