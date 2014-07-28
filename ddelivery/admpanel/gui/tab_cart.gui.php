<?php

/**
 * Панель подробного описания товара
 * @param array $row массив данных
 * @return string 
 */
function tab_cart_ddelivery($data, $option = false) {

    global $PHPShopGUI;
    $PHPShopGUI->addJSFiles('tab_cart.gui.js');

    // Обновление данных при AJAX
    if ($option == 'ajax') {
        $PHPShopOrm = new PHPShopOrm($GLOBALS['SysValue']['base']['orders']);
        $data = $PHPShopOrm->select(array('*'), array('id' => '=' . intval($data['id'])));
    }

    // Библиотека заказа
    $PHPShopOrder = new PHPShopOrderFunction($data['id']);

    $order = unserialize($data['orders']);

    // Библиотека доставки
    $PHPShopDelivery = new PHPShopDelivery($order['Person']['dostavka_metod']);

    $CART = $order['Cart'];
    $PERSON = $order['Person'];
    $cart = $CART['cart'];
    $num = null;
    $sum = null;
    $n = 1;
    if (sizeof($cart) != 0)
        if (is_array($cart))
            foreach ($cart as $val) {

                $disCart.="
<tr class=row3 onmouseover=\"show_on('r" . $val['id'] . "')\" id=\"r" . $val['id'] . "\" onmouseout=\"show_out('r" . $val['id'] . "')\" onclick=\"miniWin('adm_order_productID.php?orderID=" . $data['id'] . "&productID=" . $val['id'] . "&option=".base64_encode($val['name'])."',400,300,event)\">
 <td style=\"padding:3\">$n</td> 
  <td style=\"padding:3\">" . $val['uid'] . "</td>
  <td style=\"padding:3\">" . $val['name'] . "</td>
  <td style=\"padding:3\">" . $val['num'] . "</td>
  <td style=\"padding:3\">" . $PHPShopOrder->ReturnSumma($val['price'] * $val['num'], 0) . "</td>
  
</tr>
";

                $n++;
                $num+=$val['num'];
                $sum+=$val['price'] * $val['num'];

                // Определение и суммирование веса
                $goodid = $val['id'];
                $goodnum = $val['num'];
                $wsql = 'select weight from ' . $GLOBALS['SysValue']['base']['table_name2'] . ' where id=\'' . $goodid . '\'';
                $wresult = mysql_query($wsql);
                $wrow = mysql_fetch_array($wresult);
                $cweight = $wrow['weight'] * $goodnum;
                if (!$cweight) {
                    $zeroweight = 1;
                }

                // Один из товаров имеет нулевой вес!
                $weight+=$cweight;
            }

    // Обнуляем вес товаров, если хотя бы один товар был без веса
    if ($zeroweight) {
        $weight = 0;
    }
    //DDelivery
    $IntegratorShop = new IntegratorShop();
    $ddeliveryUI = new \DDelivery\DDeliveryUI($IntegratorShop, true);

    $ddOrder = $ddeliveryUI->getOrderByCmsID($data['uid']) ;


    $ddeliveryPrice =  $ddeliveryUI->getDeliveryPrice( $ddOrder->localId );

    $ddID = (empty($ddOrder->ddeliveryID)? 'Заявка на ddelivery.ru не создана': 'ID заявки на ddelivery.ru - ' . $ddOrder->ddeliveryID);
    $GetDeliveryPrice = $ddeliveryPrice;

    //DDelivery

   // $GetDeliveryPrice = $PHPShopOrder->getDeliverySumma();
    $disCart.="
<tr class=row3 onclick=\"miniWin('adm_order_deliveryID.php?deliveryID=" . $order['Person']['dostavka_metod'] . "&orderID=" . $data['id'] . "',400,270,event)\" onmouseover=\"show_on('r" . $n . "')\" id=\"r" . $n . "\" onmouseout=\"show_out('r" . $n . "')\">
  <td style=\"padding:3\">$n</td>
  <td style=\"padding:3\"></td>
  <td style=\"padding:3\">Доставка - " . $PHPShopDelivery->getCity() . "</td>
  <td style=\"padding:3\">1</td>
  <td style=\"padding:3\">" . $GetDeliveryPrice . "</td>
  
</tr>
";
    $n++;
    while ($n < 11) {
        $disCart.="
 <tr bgcolor=\"ffffff\">
  <td style=\"padding:3\" height=\"20\">$n</td>
  <td style=\"padding:3\"></td>
  <td style=\"padding:3\"></td>
  <td style=\"padding:3\"></td>
  <td style=\"padding:3\"></td>
</tr>
                        ";
        $n++;
    }
    $disCart.="
<tr bgcolor=\"#C0D2EC\">
  <td style=\"padding:3\" colspan=\"3\" id=pane align=center><span name=txtLang id=txtLang>Итого с учетом скидки</span> " . $PERSON['discount'] . "%</td>
  <td style=\"padding:3\"><b>" . ($num + 1) . "</b> <span name=txtLang id=txtLang>шт.</span></td>
  <td style=\"padding:3\" colspan=\"2\" align=\"center\"><b>" . ($PHPShopOrder->returnSumma($sum, $PERSON['discount']) + $GetDeliveryPrice) . "</b> " . $PHPShopOrder->default_valuta_code . "</td>
</tr>
";

    $disp = "<table width=\"100%\"  cellpadding=\"0\" cellspacing=\"0\" style=\"border: 0px;border-style:inset;\" >
	<tr>
	<td valign=\"top\">
		<div align=\"left\" style=\"width:100%;height:255;overflow:auto\"> 
	<div id=interfaces>
	<table cellpadding=\"0\" cellspacing=\"1\" width=\"100%\" border=\"0\" bgcolor=\"#808080\">
<tr>
	<td id=pane align=center width=\"10\"><img src=../img/arrow_d.gif width=7 height=7 border=0 hspace=5></td>
	<td id=pane align=center><img src=../img/arrow_d.gif width=7 height=7 border=0 hspace=5><span name=txtLang id=txtLang>Артикул</span></td>
	<td id=pane align=center><img src=../img/arrow_d.gif width=7 height=7 border=0 hspace=5><span name=txtLang id=txtLang>Наименование</span></td>
	<td id=pane align=center><img src=../img/arrow_d.gif width=7 height=7 border=0 hspace=5><span name=txtLang id=txtLang>Кол-во</span></td>
	<td id=pane align=center><img src=../img/arrow_d.gif width=7 height=7 border=0 hspace=5><span name=txtLang id=txtLang>Сумма</span> " . $PHPShopOrder->default_valuta_code . "</td>
</tr>
" . $disCart . "
</table>
</div>

	</td>
</tr>
</table>";

// Форма добавления товара и скидок
    if (empty($option))
        $disp.="
    <table width=\"100%\" cellpadding=0 cellspacing=0>
<tr>
	<td>
	<FIELDSET style=\"padding-top:5px\">
	<LEGEND><span name=txtLang id=txtLang><u>Д</u>обавить в заказ</span></LEGEND>
	<div style=\"padding:10px\">

<span name=txtLang id=txtLang>ID товара</span>: <input type=\"text\" id=\"new_product_id\" name=\"new_product_id\"> <input type=\"button\" id=btnAdd class=but value=\"Добавить\" onClick=\"DoAddProductFromOrder(new_product_id.value," . $data['id'] . ")\">
</div>
</FIELDSET >
	</td>
	<td>
	<FIELDSET style=\"padding-top:5px\">
	<LEGEND><span name=txtLang id=txtLang><u>С</u>кидка</span></LEGEND>
	<div style=\"padding:10px\">
% <input type=\"text\" id=\"new_discount\" name=\"new_discount\" value=\"" . $order['Person']['discount'] . "\"> <input type=\"button\" id=btnChange class=but value=\"Изменить\" onClick=\"DoUpdateDiscountFromOrder(new_discount.value," . $data['id'] . ")\">
</div>
</FIELDSET >
	</td>
</tr>
</table>
";

    return $disp;

}

?>
