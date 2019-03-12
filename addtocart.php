<?php
$url = 'index.php';
$sub = $_POST['prodqty'] * $_POST['prodprice'];
$product = array(array(
    'productCode'   => 12392,
    'productName'   => $_POST['prodname'],
    'productQty'   => $_POST['prodqty'],
    'productPrice'   => $_POST['prodprice'],
    'productSubtotal' => $sub
)
);
session_start();
if($_SESSION['product']):
   $_SESSION['product'] = array_merge($_SESSION['product'], $product);
    echo '<pre>';
    echo print_r($_SESSION['product']);
    echo '</pre>';
else:
    $_SESSION['product']    =  $product;
endif;
header('location:'.$url);
?>