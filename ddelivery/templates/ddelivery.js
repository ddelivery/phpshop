var topWindow = parent;

while (topWindow != topWindow.parent) {
    topWindow = topWindow.parent;
}

if (typeof(topWindow.DDeliveryIntegration) == 'undefined')
    topWindow.DDeliveryIntegration = (function() {
        var ddeliveryConfig;

        var style = document.createElement('STYLE');
        style.innerHTML = // РЎРєСЂС‹РІР°РµРј РЅРµРЅСѓР¶РЅСѓСЋ РєРЅРѕРїРєСѓ
            " #delivery_info_ddelivery_all a{display: none;} " +
            " #ddelivery_popup { display: inline-block; vertical-align: middle; margin: 10px auto; width: 1000px; height: 650px;} " +
            " #ddelivery_container {  z-index: 9999;display: none; width: 100%; height: 100%; text-align: center;  } " +
            " #ddelivery_container:before { display: inline-block; height: 100%; content: ''; vertical-align: middle;} " +
            " #ddelivery_cover {overflow: auto;position: fixed; top: 0; left: 0; right:0; bottom:0; z-index: 9000; width: 100%; height: 100%; background-color: #000; background: rgba(0, 0, 0, 0.5); filter: progid:DXImageTransform.Microsoft.gradient(startColorstr = #7F000000, endColorstr = #7F000000); } ";
        var body = document.getElementsByTagName('body')[0];
        body.appendChild(style);
        var div = document.createElement('div');
        div.innerHTML = '<div id="ddelivery_popup"></div>';
        div.id = 'ddelivery_container';
        body.appendChild(div);


        function UpdateDelivery2(xid, order_id) {
            var req = new Subsys_JsHttpRequest_Js();
            var sum = document.getElementById('OrderSumma').value;
            var wsum = document.getElementById('WeightSumma').innerHTML;
            req.onreadystatechange = function() {
                if (req.readyState == 4) {
                    if (req.responseJS) {
                        document.getElementById('DosSumma').innerHTML = (req.responseJS.delivery || '');
                        document.getElementById('d').value = xid;
                        document.getElementById('TotalSumma').innerHTML = (req.responseJS.total || '');
                        document.getElementById('seldelivery').innerHTML = (req.responseJS.dellist || '');
                    }
                }
            }
            req.caching = false;
            var dir = dirPath();

            req.open('POST', dir + '/phpshop/ajax/delivery.php', true);
            req.send({
                xid: xid,
                sum: sum,
                wsum: wsum,
                order_id: order_id
            });
        }


        function showPrompt() {
            var cover = document.createElement('div');
            cover.id = 'ddelivery_cover';
            cover.appendChild(div);
            document.body.appendChild(cover);
            document.getElementById('ddelivery_container').style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.getElementById('ddelivery_popup').innerHTML = '';
        }

        function hideCover() {
            document.body.removeChild(document.getElementById('ddelivery_cover'));
            document.getElementsByTagName('body')[0].style.overflow = "";
        }


        function orderCallBack(data, dostavka_metod) {
            document.getElementsByName('name_person')[0].value = data.userInfo.firstName;
            document.getElementsByName('tel_name')[0].value = data.userInfo.toPhone;
            document.getElementsByName('mail')[0].value = data.userInfo.toEmail;
            document.getElementById('ddelivery_id').value = data.orderId;
            document.getElementsByName('adr_name')[0].value = data.comment;
        }

        function getActiveDelivery(){
            //var dostavka_metod = $('input[name="dostavka_metod"]:checked ').val();
            var dostavka_metod = parseInt( document.getElementById('dostavka_metod').value );
            return dostavka_metod;
        }

        function disablePaymentDelivery(ids){
            if( ids.length > 0 ){
                var index;
                var order_metod = document.getElementById('order_metod');
                var option = order_metod.getElementsByTagName('option');
                for (index = 0; index < option.length; index++){
                    if( ids.indexOf(option[index].value) ){
                        option[index].disabled = true;
                    }
                }

            }
        }

        function enablePayment(){
            $('input[name="order_metod"]').each(function(){
                $(this).parent().parent().css('display', 'block');
            });
            $('.dd_comment').text("");
        }

        function getSerializedForm(name){
            var index;
            var form = document.getElementsByName(name)[0];
            var input = form.getElementsByTagName('input');
            var result = '';
            for (index = 0; index < input.length; index++) {
                result += input[index].value + '=' + input[index].name + '&'
            }
            return result;
        }

        return{
            openPopup: function(){

                showPrompt();
                var callback = {
                    close: function(){
                        hideCover();
                    },
                    change: function(data) {
                        var dostavka_metod = getActiveDelivery();
                        orderCallBack(data, dostavka_metod);
                        //UpdateDeliveryJq2( dostavka_metod, data.orderId );
                        UpdateDelivery2(dostavka_metod, data.orderId);
                        console.log(data);
                        disablePaymentDelivery( data.payment );
                        hideCover();
                    }
                };
                ///disablePaymentDelivery(12);
                var forma_order = getSerializedForm('forma_order');
                DDelivery.delivery('ddelivery_popup', ddeliveryConfig.url + '?' + forma_order /*'@DDorderUrl@' + paramsString */, { }, callback);
                return void(0);
            },

            init:function( ddConfig ){
                ddeliveryConfig = ddConfig;
                var ddelivery_id = document.createElement('input');
                ddelivery_id.id = 'ddelivery_id';
                ddelivery_id.type = 'hidden';
                ddelivery_id.name = 'ddelivery_id';
                ddelivery_id.value = '';
                var forma_order = document.getElementsByName('forma_order');
                forma_order[0].appendChild(ddelivery_id);


                forma_order[0].onsubmit = function(){
                        alert('bbbbbbbbbb');
                        return false;
                }
                /*
                var dostavka_metod = document.getElementById('dostavka_metod');
                dostavka_metod.onchange = function(){
                    alert('xxx');
                    return true;
                }
                */
                /*
                $('#seldelivery').on('click', function(){
                    $('#ddelivery_id').val("");
                    enablePayment();
                });
                */
                /*
                window.onload = function(){
                    var forma_order = document.getElementsByName('forma_order');
                    forma_order[0].appendChild(ddelivery_id);
                }
                */

                //console.log( ddelivery_id );
                //$('#forma_order').append('<input type="hidden" id="ddelivery_id" name="ddelivery_id" value="">');
                /*
                try{
                    $(document).ready(function(){
                        ddeliveryConfig = ddConfig;

                        $('#seldelivery').on('click', function(){
                            $('#ddelivery_id').val("");
                            enablePayment();
                        });

                        $('#forma_order').append('<input type="hidden" id="ddelivery_id" name="ddelivery_id" value="">');
                        $('#forma_order').submit(function(){
                            var dostavka_metod = getActiveDelivery(); //document.getElementById("dostavka_metod").value;
                                if( ddeliveryConfig.DDeliveryID.indexOf(parseInt(dostavka_metod)) != -1 ){
                                if( parseInt( $('#ddelivery_id').val() ) > 0 ){
                                    return true;
                                }
                                alert('Уточните выбор доставки');
                                return false;
                            }
                            return true;
                        });



                    });
                }catch (e){
                    alert('Ошибка!');
                }
                */
            }
        }
    })();

DDeliveryIntegration.init( DDeliveryConfig );