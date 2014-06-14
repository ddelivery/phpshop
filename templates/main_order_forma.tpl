@ComStartReg@
<script src="phpshop/modules/ddelivery/class/html/js/ddelivery.js"></script>
<script src="phpshop/modules/ddelivery/class/example/assets/jquery.min.js"></script>
<script type="text/javascript">var jQuery_1_11 = jQuery.noConflict();</script>
<link rel="stylesheet" type="text/css" href="phpshop/modules/ddelivery/class/example/assets/the-modal.css" media="screen" />
<link rel="stylesheet" type="text/css" href="phpshop/modules/ddelivery/class/example/assets/demo-modals.css" media="screen" />
<script src="phpshop/modules/ddelivery/class/example/assets/jquery.the-modal.js"></script>

<script type="text/javascript">

    jQuery_1_11(document).ready(function() {

        jQuery_1_11(document).on('click', '.trigger',function(){
            jQuery_1_11('#test-modal').modal().open();
            DDeliveryStart();

        });

        jQuery_1_11(document).on('submit', '#forma_order',function(){
                return false;
        });
    });
</script>

<div class="modal" id="test-modal" style="display: none">
    <div id="ddelivery"></div>
</div>

<div  id=allspecwhite style="margin-bottom:20px">

<img src="images/shop/icon_key.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">
<a href="/users/register.html" class="b">Зарегистрируйтесь</a> и получите дополнительные возможности и <b>скидки</b>.
</div>
@ComEndReg@

<script type="text/javascript">
    // Просчет доставки
    function OrderChekDDelivery()
    {
        var DDid = @DDid@;
        dostavka_metod = document.getElementById("dostavka_metod").value;
        ddelivery_order_id = document.getElementById('ddelivery_order_id').value;
        if( dostavka_metod == DDid && !ddelivery_order_id )
        {
            alert("Выберите способ доставки DDelivery");
            return false;
        }
        var s1=window.document.forms.forma_order.mail.value;
        var s2=window.document.forms.forma_order.name_person.value;
        var s3=window.document.forms.forma_order.tel_name.value;
        var s4=window.document.forms.forma_order.adr_name.value;
        if (document.getElementById("makeyourchoise").value=="DONE") {
            bad=0;
        } else {
            bad=1;
        }

        if (s1=="" || s2=="" || s3=="" || s4=="") {
            alert("Ошибка заполнения формы заказа.\nДанные отмеченные флажками заполнять обязательно! ");
        } else if (bad==1) {
            alert("Ошибка заполнения формы заказа.\nВыберите доставку!");
        } else{

            document.forma_order.submit();
        }
    }
    function UpdateDelivery2(xid, order_id) {


        var req = new Subsys_JsHttpRequest_Js();
        var sum = document.getElementById('OrderSumma').value;
        var wsum = document.getElementById('WeightSumma').innerHTML;
        //var order_id = ddelivery_order_id = document.getElementById('ddelivery_order_id').value;

        req.onreadystatechange = function() {
            if (req.readyState == 4) {
                if (req.responseJS) {
                    document.getElementById('DosSumma').innerHTML = (req.responseJS.delivery||'');
                    document.getElementById('d').value = xid;
                    document.getElementById('TotalSumma').innerHTML = (req.responseJS.total||'');
                    document.getElementById('seldelivery').innerHTML = (req.responseJS.dellist||'');
                }
            }
        }

        req.caching = false;
        // Подготваливаем объект.
        // Реальное размещение
        //var dir=dirPath();

        req.open('POST', 'phpshop/ajax/delivery.php', true);
        req.send({
            xid: xid,
            sum: sum,
            wsum: wsum,
            order_id: order_id
        });

    }
        function DDeliveryStart()
        {
            //document.getElementById('parent_popup').style.display='block'
            ddelivery_order_id = document.getElementById('ddelivery_order_id');
            if( ddelivery_order_id.value != '' )
            {
                orderID = ddelivery_order_id.value;
            }
            else
            {
                orderID = 0;
            }
            //alert(orderID);
            var params = {
                orderId: orderID // Если у вас есть id заказа который изменяется, то укажите его в этом параметре
            };
            var callback = {
                close: function(){
                    jQuery_1_11('#test-modal').modal().close();
                    //alert('Окно закрыто');
                },
                change: function(data) {

                    jQuery_1_11('#test-modal').modal().close();
                    UpdateDelivery2( @DDid@, data.orderId );
                    console.log(data.comment);
                    jQuery_1_11('#adr_name').val(data.comment);
                    //console.log(data);
                    orderCallBack(data);
                    //UpdateDelivery2(5736, data.orderId);
                    //alert(data.comment+ ' интернет магазину нужно взять с пользователя за доставку '+data.clientPrice+' руб. OrderId: '+data.orderId);
                }
            };
            DDelivery.delivery('ddelivery', '@bidloOrder@', params, callback);

        }

        function orderCallBack( data )
        {
            //console.log(data);
            //document.getElementById('DosSumma').innerHTML = (data.clientPrice);
            if( data.userInfo.toStreet!= null )
            {
                document.getElementById('adr_name').value = data.userInfo.toStreet + ' ' + data.userInfo.toHouse + ' ' + data.userInfo.toFlat + ' ' ;
            }
            mail = document.getElementsByName('mail');
            if(data.userInfo.toEmail!=null)
            {
                mail[0].value = data.userInfo.toEmail;
            }

            //total = document.getElementById('TotalSumma').value;
            //document.getElementById('TotalSumma').innerHTML = (parseInt( total ) + data.clientPrice);
            ddelivery_order_id = document.getElementById('ddelivery_order_id');
            ddelivery_order_id.value = data.orderId;
            name_person = document.getElementsByName('name_person');
            name_person[0].value = data.userInfo.firstName + ' ' + data.userInfo.secondName;
            tel_name = document.getElementsByName('tel_name');
            tel_name[0].value = data.userInfo.toPhone;
        }

    function sendCheck()
    {
        dostavka_metod = document.getElementById('dostavka_metod').value;
        ddelivery_order_id = document.getElementById('ddelivery_order_id');
        alert(ddelivery_order_id.value);
        /*
        if( dostavka_metod == 5736 && (!ddelivery_order_id.value))
        {
            alert("Уточните список доставки DDelivery");
            return false;
        }
        return true;
        */

    }

</script>

<form onsubmit="return false;" method="post" name="forma_order" id="forma_order" action="/done/" >
<table  cellpadding="5" cellspacing="0" width=100% >
<tr>
	<td align="right">
	<b>Заказ №</b>
	</td>
	<td>
	<input type="text" name=ouid style="width:50px; height:18px; font-family:tahoma; font-size:11px ; color:#9e0b0e; background-color:#f2f2f2;" value="@orderNum@"  readonly="1"> <b>/</b>
    <input type="text" style="width:50px; height:18px; font-family:tahoma; font-size:11px ; color:#9e0b0e; background-color:#f2f2f2;" value="@orderDate@"  readonly="1">
	</td>	
</tr>
<tr>
   <td align="right" valign="top">Доставка</td>
   <td>
   @orderDelivery@
   </td>
</tr>
<tr valign="top">
    <td align="right">
	E-mail:
	</td>
	<td>
	<input type="text" name="mail" style="width:300px; height:18px; font-family:tahoma; font-size:11px ; color:#4F4F4F " maxlength="30" value="@UserMail@" @formaLock@><img src="images/shop/flag_green.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">
	</td>
</tr>
<tr>
	<td align="right" class=tah12>
    Контактное лицо:
	</td>
	<td>
	<input type="text" name="name_person" style="width:300px; height:18px; font-family:tahoma; font-size:11px ; color:#4F4F4F " maxlength="30" value="@UserName@" @formaLock@><img src="images/shop/flag_green.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">
	</td>
</tr>

<!--
<tr>
	<td align="right" >
	ИНН:
	</td>
	<td>
	<input type="text" name="org_inn" style="width:150px; height:18px; font-family:tahoma; font-size:11px ; color:#4F4F4F " maxlength="50" value="@UserInn@" @formaLock@>
	</td>
</tr> 
<tr>
	<td align="right" >
	КПП:
	</td>
	<td>
	<input type="text" name="org_kpp" style="width:150px; height:18px; font-family:tahoma; font-size:11px ; color:#4F4F4F " maxlength="50" value="@UserKpp@" @formaLock@>
	</td>
</tr> 

-->

<tr>
	<td align="right">
	Телефон:
	</td>
	<td>
	<input type="text" name="tel_code" style="width:50px; height:18px; font-family:tahoma; font-size:11px ; color:#4F4F4F " maxlength="5" value="@UserTelCode@"> -
	<input type="text" name="tel_name" style="width:150px; height:18px; font-family:tahoma; font-size:11px ; color:#4F4F4F " maxlength="30" value="@UserTel@"><img src="images/shop/flag_green.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">
	</td>
</tr>
<tr>
	<td align="right">
	Время доставки:
	</td>
	<td>
	от <input type="text" name="dos_ot" style="width:50px; height:18px; font-family:tahoma; font-size:11px ; color:#4F4F4F " maxlength="5">ч.&nbsp;&nbsp;&nbsp;
    до
<input type="text" name="dos_do" style="width:50px; height:18px; font-family:tahoma; font-size:11px ; color:#4F4F4F " maxlength="5">ч. 
	</td>
</tr>
<tr>
	<td align="right" class=tah12>
	Адрес и <br>
	дополнительная<br>
	информация:
	</td>
	<td>
	<textarea style="width:300px; height:100px; font-family:tahoma; font-size:11px ; color:#4F4F4F " name="adr_name" id="adr_name">@UserAdres@</textarea><img src="images/shop/flag_green.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">
        <span id="pickpoint_phpshop">1</span>
        </td>
</tr>
<tr>
	<td align="right" >
	КОД ДЛЯ СКИДКИ:
	</td>
	<td>
	<input type="text" name="org_name" style="width:300px; height:18px; font-family:tahoma; font-size:11px ; color:#4F4F4F " maxlength="100" value="@UserComp@" @formaLock@>
	</td>
</tr>
<tr>
   <td align="right">Тип оплаты <br>покупки</td>
   <td>
   @orderOplata@
   </td>
</tr>
<tr>
  <td></td>
  <td>
  <div  id=allspecwhite><img src="images/shop/comment.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">Данные, отмеченные <b>флажками</b> обязательны для заполнения.<br>
</div>

  </td>
</tr>
<tr>
    <td colspan="2" align="center">
	<p><br></p>
	<table align="center">
<tr>
<td>
	<img src="images/shop/brick_error.gif" border="0" align="absmiddle">
	<a href="javascript:forma_order.reset();" class=link>Очистить форму</a></td>
	<td width="20"></td>
	<td id="order_butt3"><a href="javascript::void(0);" onclick="OrderChekDDelivery();" class=link>ОФОРМИТЬ ЗАКАЗ</a></td>

</tr>
</table>
    <input type="hidden" name="ddelivery_order_id" id="ddelivery_order_id" value="">
	<input type="hidden" name="send_to_order" value="ok" >
	<input type="hidden" name="d" id="d" value="@deliveryId@">
	<input type="hidden" name="nav" value="done">
    </td>
</tr>
</table>
</form>
