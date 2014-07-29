<?php
function enqueMessage( $message, $site ){
    $message =  strip_tags($message) ;
    $site = strip_tags($site) ;
    $mysqli = new mysqli("localhost", "c1dba", "OH2AgbFiU", "c1logs");
    $result = $mysqli->query("INSERT INTO logs( message, site ) VALUES ('$message','$site')");
    $to  = 'ddeliverylogs@gmail.com';
    $subject = 'errorlog ' . $site;
    $message = $site . '<br />'. $message;
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
    mail($to, $subject, $message, $headers);
}
if( !empty( $_POST['apikey'] ) ){
    $server_array = array( 'http://stage.ddelivery.ru:80/api/v1/' . $_POST['apikey'] . '/shop_info.json',
                           'http://cabinet.ddelivery.ru:80/api/v1/' . $_POST['apikey'] . '/shop_info.json');
    $is_test = (int) $_POST['testmode'];
    $server_url = ( ($is_test)?$server_array[0]:$server_array[1] );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_URL, $server_url . '?api_key=' . $_POST['apikey']);
    $result = json_decode( curl_exec($curl) );
    if( ((int)$result->success) && ((int)$result->response->_id) ){
        enqueMessage( $_POST['message'], $_POST['url'] );
    }
}

?>