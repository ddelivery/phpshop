<?php

function checkStore_productoption_hook($obj,$row) {
   
    $obj->set('productOption1',$row['option1']);
    $obj->set('productOption2',$row['option2']);
    $obj->set('productOption3',$row['option3']);
    $obj->set('productOption4',$row['option4']);
    $obj->set('productOption5',$row['option5']);
    return true;
}
 
$addHandler=array
        (
        'checkStore'=>'checkStore_productoption_hook'
);
?>
