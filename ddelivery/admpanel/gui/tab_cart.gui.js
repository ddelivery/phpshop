/**
 * JS Библиотека панели корзины заказа tab_cart.gui.php
 */


// Печатная форма
function DoPrint(path){
    window.open(path,"_blank","dependent=1,left=0,top=0,width=650,height=650,location=1,menubar=1,resizable=1,scrollbars=1,status=1,titlebar=1,toolbar=1");
}


// Редактируем скидку из заказа
function DoUpdateDiscountFromOrder(xid,uid) {
    var req = new JsHttpRequest();
    req.onreadystatechange = function() {
        if (req.readyState == 4) {
            document.getElementById('interfaces').innerHTML = req.responseJS.interfaces;
        //setTimeout("window.close()",0);
        }
    }
    req.open(null, 'ajax/cart.ajax.php?do=discount', true);
    req.send( {
        xid: xid,
        uid: uid
    } );
}

// Новый товар в заказ
function DoAddProductFromOrder(xid,uid) {
    if(xid!=""){
        if(confirm("Внимание!\nВы действительно хотите добавить новый товар в заказ?")){
            var req = new JsHttpRequest();
            req.onreadystatechange = function() {
                if (req.readyState == 4) {
                    document.getElementById('interfaces').innerHTML = req.responseJS.interfaces;
                }
            }
            req.open(null, 'ajax/cart.ajax.php?do=add', true);
            req.send( {
                xid: xid,
                uid: uid
            } );
        }
    }
    else alert("Укажите ID товара!");
}

// Редактируем доставку из заказа
function DoUpdateDeliveryFromOrder(xid,uid) {
    var req = new JsHttpRequest();
    req.onreadystatechange = function() {
        if (req.readyState == 4) {
            window.opener.document.getElementById('interfaces').innerHTML = req.responseJS.interfaces;
            setTimeout("window.close()",0);
        }
    }
    req.open(null, 'ajax/cart.ajax.php?do=delivery', true);
    req.send( {
        xid: xid,
        uid: uid
    } );
}

// Редактируем товар из заказа
function DoUpdateFromOrder(xid,uid,name,num,price) {
    var req = new JsHttpRequest();
    req.onreadystatechange = function() {
        if (req.readyState == 4) {
            window.opener.document.getElementById('interfaces').innerHTML = req.responseJS.interfaces;
            setTimeout("window.close()",0);
        }
    }
    req.open(null, 'ajax/cart.ajax.php?do=update', true);
    req.send( {
        xid: xid,
        uid: uid,
        name: name,
        num: num,
        price: price
    } );
}

// Удаляем товар из заказа
function DoDelFromOrder(xid, uid) {
    if(confirm("Внимание!\nДанная операция может привести к потере позиции.\nВы действительно хотите выполнить данную команду?")){
        var req = new JsHttpRequest();
        req.onreadystatechange = function() {
            if (req.readyState == 4) {
                window.opener.document.getElementById('interfaces').innerHTML = req.responseJS.interfaces;
                setTimeout("window.close()",0);
            }
        }
        req.open(null, 'ajax/cart.ajax.php?do=delete', true);
        req.send( {
            xid: xid,
            uid: uid
        } );
    }
}
