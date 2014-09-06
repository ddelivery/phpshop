@ComStartReg@
<script src="phpshop/modules/ddelivery/class/html/js/ddelivery.js"></script>

<style type="text/css">
    #ddbutton {
        background-image: none;
        background-color: #33BC33;
        background-repeat: repeat-x;
        display: inline-block;
        padding: 3px 6px 3px;
        color: #fff;
        text-decoration: none;
        text-transform: uppercase;
        font-weight: bold;
        line-height: 1;
        -moz-border-radius: 5px;
        -webkit-border-radius: 5px;
        border-radius: 5px;
        -moz-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        -webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        text-shadow: 0 -1px 1px rgba(0, 0, 0, 0.25);
        border: 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.25);
        position: relative;
        cursor: pointer;
        font: 12px Tahoma, sans-serif !important;
        margin:0 3px;
        text-decoration: none !important;
    }
</style>





<script type="text/javascript">
    window.onload  = function(){
        var dostavka_metod = document.getElementById("seldelivery");

        dostavka_metod.onclick = function(){
            if( !DDeliveryIntegration.isDDeliveryWay()){
                enableAllPayments();
            }
        }
    }
    function enableAllPayments(){
        var order_metod =  document.getElementById("order_metod");
        for (var i = 0; i < order_metod.length; i++){
            order_metod.options[i].disabled = "";
        }
    }

    // Просчет доставки
    function findInArray( array, value ){
        var length = array.length;
        for ( var i = 0; i< length; i++ ){
            if( parseInt( array[i] ) == parseInt( value ) ){
                return true;
            }
        }
        return false;
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

        req.open('POST', 'phpshop/ajax/delivery.php', true);
        req.send({
            xid: xid,
            sum: sum,
            wsum: wsum,
            order_id: order_id
        });
    }


    function orderCallBack( data ){
        //document.getElementById('DosSumma').innerHTML = (data.clientPrice);
        if( data.userInfo.toStreet != null )
        {
            var address = data.userInfo.toStreet;

            if( data.userInfo.toHouse != null ){
                address += ( ' ' + data.userInfo.toHouse);
            }

            if( data.userInfo.toHousing != null ){
                address += ( ' ' + data.userInfo.toHouse);
            }

            if( data.userInfo.toFlat != null ){
                address += ( ' ' + data.userInfo.toFlat);
            }
            document.getElementById('adr_name').value = address;
        }
        mail = document.getElementsByName('mail');
        if(data.userInfo.toEmail!=null)
        {
            mail[0].value = data.userInfo.toEmail;
        }

        ddelivery_order_id = document.getElementById('ddelivery_order_id');
        ddelivery_order_id.value = data.orderId;
        name_person = document.getElementsByName('name_person');
        name_person[0].value = data.userInfo.firstName ;
        tel_name = document.getElementsByName('tel_name');
        tel_name[0].value = data.userInfo.toPhone;
    }



    var topWindow = parent;

    while(topWindow != topWindow.parent) {
        topWindow = topWindow.parent;
    }

    if(typeof(topWindow.DDeliveryIntegration) == 'undefined')
        topWindow.DDeliveryIntegration = (function(){
            var th = {};

           var ddid = @DDid@;

            var goodPaymentVariants = '';
            var status = 'XXX';
            th.getStatus = function(){
                return status;
            };
            function hideCover() {

                document.body.removeChild(document.getElementById('ddelivery_cover'));
            }

            function showPrompt() {
                var cover = document.createElement('div');
                cover.id = 'ddelivery_cover';
                document.body.appendChild(cover);
                document.getElementById('ddelivery_container').style.display = 'block';
            }
            function disablePayment(){
                var json = goodPaymentVariants;
                var order_metod =  document.getElementById("order_metod");
                for (var i = 0; i < order_metod.length; i++){
                    if( !findInArray(json, order_metod.options[i].value) ){
                        order_metod.options[i].disabled = "disabled";
                        console.log('in ' + order_metod.options[i].value);
                    }else{
                        order_metod.options[i].disabled = "";
                    }
                }
            }
            th.isDDeliveryWay = function( ){
                dostavka_metod = document.getElementById("dostavka_metod").value;
                if( findInArray(ddid, dostavka_metod) ){
                    return true;
                }
                return false;
            }
            th.isValidPaymentWay = function(way){
                if( goodPaymentVariants != '' ){
                    console.log(goodPaymentVariants);
                    if( findInArray(goodPaymentVariants, way) ){
                        return true;
                    }
                }
                return false;
            }
            th.getDDeliveryIds = function(){
                return ddid;
            }
            th.openPopup = function(){
                //console.log(ddid);
                showPrompt();
                document.getElementById('ddelivery_popup').innerHTML = '';
                var callback = {
                    close: function(){
                        hideCover();
                        document.getElementById('ddelivery_container').style.display = 'none';
                    },
                    change: function(data) {
                        goodPaymentVariants = JSON.parse(data.payment);
                        disablePayment();
                        DDid = document.getElementById("dostavka_metod").value;
                        document.getElementById("DosSumma2").innerHTML = data.clientPrice;// data.clientPrice
                        document.getElementById("ddelivery_comment").innerHTML = data.comment;

                        UpdateDelivery2( DDid, data.orderId );
                        orderCallBack(data);
                        console.log(data);
                        hideCover();
                        document.getElementById('ddelivery_container').style.display = 'none';
                    }
                };
                var forma_order = document.getElementById("forma_order");
                var paramsString = '';
                for(var i = 0; i < forma_order.elements.length; i++){
                    el = forma_order.elements[i];

                    if( el.value != '' && el.name != '' ){
                        if( i == 0 ){ devider = '?' }else{ devider = '&' }
                        paramsString += ( devider + el.name + '=' + encodeURIComponent( el.value ));
                    }

                }
                DDelivery.delivery('ddelivery_popup', '@DDorderUrl@' + paramsString, { }, callback);

                return void(0);
            };
            var style = document.createElement('STYLE');
            style.innerHTML = // РЎРєСЂС‹РІР°РµРј РЅРµРЅСѓР¶РЅСѓСЋ РєРЅРѕРїРєСѓ
                    " #delivery_info_ddelivery_all a{display: none;} " +
                            " #ddelivery_popup { display: inline-block; vertical-align: middle; margin: 10px auto; width: 1000px; height: 650px;} " +
                            " #ddelivery_container { position: fixed; top: 0; left: 0; z-index: 9999;display: none; width: 100%; height: 100%; text-align: center;  } " +
                            " #ddelivery_container:before { display: inline-block; height: 100%; content: ''; vertical-align: middle;} " +
                            " #ddelivery_cover {  position: fixed; top: 0; left: 0; z-index: 9000; width: 100%; height: 100%; background-color: #000; background: rgba(0, 0, 0, 0.5); filter: progid:DXImageTransform.Microsoft.gradient(startColorstr = #7F000000, endColorstr = #7F000000); } ";
            var body = document.getElementsByTagName('body')[0];
            body.appendChild(style);
            var div = document.createElement('div');
            div.innerHTML = '<div id="ddelivery_popup"></div>';
            div.id = 'ddelivery_container';
            body.appendChild(div);
            return th;
        })();
    var DDeliveryIntegration = topWindow.DDeliveryIntegration;


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

    function OrderChekDDelivery()
    {

        ddelivery_order_id = document.getElementById('ddelivery_order_id').value;
        order_metod = document.getElementById('order_metod').value;


        if( ( DDeliveryIntegration.isDDeliveryWay()) && !( parseInt(ddelivery_order_id) > 0) ){
            alert("Выберите способ доставки DDelivery");
            return false;
        }

        if( ( DDeliveryIntegration.isDDeliveryWay()) && !( DDeliveryIntegration.isValidPaymentWay(order_metod)) ){
            alert("Выберите доступный" +
                    " способ оплаты");
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

    if (!Array.prototype.indexOf)
    {
        Array.prototype.indexOf = function(elt /*, from*/)
        {
            var len = this.length >>> 0;

            var from = Number(arguments[1]) || 0;
            from = (from < 0)
                    ? Math.ceil(from)
                    : Math.floor(from);
            if (from < 0)
                from += len;

            for (; from < len; from++)
            {
                if (from in this &&
                        this[from] === elt)
                    return from;
            }
            return -1;
        };
    }

</script>

<form onsubmit="return false;" method="post" name="forma_order" id="forma_order" action="/done/" >
    <table  cellpadding="5" cellspacing="0" width=100% >



        <!--начало формы -->


        <tr>
            <td align="right">
                <b>Заказ №</b>
            </td>
            <td>
                <input type="text" name=ouid style="width:80px; height:24px; font-family:tahoma; font-size:14px ; color:#9e0b0e; background-color:#f2f2f2;" value="@orderNum@"  readonly="1"> <b>/</b>
                <input type="text" style="width:80px; height:24px; font-family:tahoma; font-size:14px ; color:#9e0b0e; background-color:#f2f2f2;" value="@orderDate@"  readonly="1">
            </td>
        </tr>


        <tr>
            <td align="right" class=tah12>

            </td>
            <td><B>КОНТАКТНАЯ ИНФОРМАЦИЯ</B>:

            </td>

        </tr>

        <tr>
            <td align="right" class=tah12>
                Контактное лицо:
            </td>
            <td>
                <input type="text" name="name_person" placeholder="пример: Петров Петр Петрович" style="width:400px; height:24px; font-family:tahoma; font-size:14px ; color:#000000 " maxlength="30" value="@UserName@" @formaLock@><img src="images/shop/flag_green.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">
            </td>
        </tr>
        <tr>
            <td align="right">
                Телефон:
            </td>
            <td>
                <!--	<input type="text" name="tel_code" style="width:50px; height:18px; font-family:tahoma; font-size:11px ; color:#4F4F4F " maxlength="5" value="@UserTelCode@"> -->
                <input type="text" name="tel_name" placeholder="пример: +7 921 211 21 21" style="width:400px; height:24px; font-family:tahoma; font-size:14px ; color:#000000 " maxlength="30" value="@UserTel@"><img src="images/shop/flag_green.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">
            </td>
        </tr>
        <tr valign="top">
            <td align="right">
                E-mail:
            </td>
            <td>
                <input type="text" name="mail" placeholder="пример: user@adress.ru" style="width:400px; height:24px; font-family:tahoma; font-size:14px ; color:#000000" maxlength="30" value="@UserMail@" @formaLock@><img src="images/shop/flag_green.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">
            </td>
        </tr>




        <tr><td></td>
            <td><B>ВЫБЕРИТЕ СПОСОБ ДОСТАВКИ</B>:

            </td>

        </tr>
        <tr>
            <td align="right" valign="top">Доставка</td>
            <td>
                @orderDelivery@
                <div id="ddelivery_comment" style="color:#9e0b0e;margin-top:10px;font-size:14px;"></div>
                <div style="margin-top:10px;font-size:14px;">Доставка: <span id="DosSumma2">0</span> @currency@</div>
            </td>
        </tr>




        <tr>
            <td align="right" class=tah12>
                Адрес и <br>
                дополнительная<br>
                информация:
            </td>
            <td>
                <textarea style="width:400px; height:100px; font-family:tahoma; font-size:14px ; color:#000000 " name="adr_name" id="adr_name">@UserAdres@</textarea><img src="images/shop/flag_green.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">
                <span id="pickpoint_phpshop"></span>
            </td>
        </tr>



        <tr>
            <td align="right" class=tah12>

            </td>

        <tr>
            <td align="right" class=tah12>

            </td>
            <td><B>ВЫБЕРИТЕ СПОСОБ ОПЛАТЫ</B>:

            </td>

        </tr>

        <tr>
            <td align="right">Тип оплаты <br>покупки</td>
            <td>
                @orderOplata@
            </td>
        </tr>

        <tr>
            <td align="right" valign="middle">
                КОД ДЛЯ СКИДКИ:
            </td>
            <td>
                <input type="text" name="org_name" style="width:400px; height:24px; font-family:tahoma; font-size:14px ; color:#000000 " maxlength="100" value="@UserComp@" @formaLock@><br>
                <!--<div style="margin-top:10px;font-size:14px;">Итого к оплате: <span id="TotalSumma2">@total@</span> @currency@</div>-->
            </td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <p><br></p>
                <table align="center">

                    <!-- конец формы старой -->



                    <tr>
                        <td>
                            &nbsp;</td>
                        <td width="20"></td>
                        <td id="order_butt3"><span id="b_order_crt" >
<img src="images/shop/brick_go.gif"  border="0" align="absmiddle">
<a href="javascript::void(0);" onclick="OrderChekDDelivery();" class=link>ОФОРМИТЬ ЗАЯВКУ</a></span></td>



                    </tr>
                </table>
                <input type="hidden" name="ddelivery_order_id" id="ddelivery_order_id" value="">
                <input type="hidden" name="send_to_order" value="ok" >
                <input type="hidden" name="d" id="d" value="@deliveryId@">
                <input type="hidden" name="nav" value="done">
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td>
                <div  id=allspecwhite><img src="images/shop/comment.gif" alt="" width="16" height="16" border="0" hspace="5" align="absmiddle">После того, как вы подтвердите заказ на сайте, Вам позвонит менеджер, чтобы подвердить его и сообщит всю необходимую информацию.<br>
                </div>

            </td>
        </tr>
    </table>
</form>
