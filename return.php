<?php
session_start();
//We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
include_once("paypal.class.php");
//Paypal redirects back to this page using ReturnURL, We should receive TOKEN and Payer ID
if(isset($_GET["token"]) && isset($_GET["PayerID"]))
{
	//we will be using these two variables to execute the "DoExpressCheckoutPayment"
	//Note: we haven't received any payment yet.
	
	$token = $_GET["token"];
	$payer_id = $_GET["PayerID"];
	
	//get session variables
	$paypal_product = $_SESSION["paypal_products"];
	$paypal_data = '';
	$ItemTotalPrice = 0;

	foreach($_SESSION['product'] as $key=>$productList)
    {
		$paypal_data .= '&L_PAYMENTREQUEST_0_NUMBER'.$key.'='.urlencode($productList['productCode']);
        $paypal_data .= '&L_PAYMENTREQUEST_0_NAME'.$key.'='.urlencode($productList['productName']);
        $paypal_data .= '&L_PAYMENTREQUEST_0_AMT'.$key.'='.urlencode($productList['productPrice']);		
		$paypal_data .= '&L_PAYMENTREQUEST_0_QTY'.$key.'='. urlencode($productList['productQty']);
		
		// item price X quantity
        $subtotal = ($productList['productPrice'] * $productList['productQty']);
		
        //total price
        $ItemTotalPrice = $ItemTotalPrice + $subtotal;

	  }
	  



	$padata = 	'&TOKEN='.urlencode($token).
				'&PAYERID='.urlencode($payer_id).
				'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").
				$paypal_data.
				'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($ItemTotalPrice).
				'&PAYMENTREQUEST_0_TAXAMT='.urlencode($paypal_product['assets']['tax_total']).
				'&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($paypal_product['assets']['shippin_cost']).
				'&PAYMENTREQUEST_0_HANDLINGAMT='.urlencode($paypal_product['assets']['handaling_cost']).
				'&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($paypal_product['assets']['shippin_discount']).
				'&PAYMENTREQUEST_0_INSURANCEAMT='.urlencode($paypal_product['assets']['insurance_cost']).
				'&PAYMENTREQUEST_0_AMT='.urlencode($paypal_product['assets']['grand_total']).
				'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($PayPalCurrencyCode);

	//We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
	$paypal= new MyPayPal();
	$httpParsedResponseAr = $paypal->PPHttpPost('DoExpressCheckoutPayment', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);
	
	//Check if everything went ok..
	if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
	{

			echo '<h2>Success</h2>';
			echo 'Your Transaction ID : '.urldecode($httpParsedResponseAr["PAYMENTINFO_0_TRANSACTIONID"]);
				if('Completed' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"])
				{
					echo '<div style="color:green">Payment Received! Your product will be sent to you very soon!</div>';
				}
				elseif('Pending' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"])
				{
					echo '<div style="color:red">Transaction Complete, but payment is still pending! '.
					'You need to manually authorize this payment in your <a target="_new" href="http://www.paypal.com">Paypal Account</a></div>';
				}
				$padata = 	'&TOKEN='.urlencode($token);
				$paypal= new MyPayPal();
				$httpParsedResponseAr = $paypal->PPHttpPost('GetExpressCheckoutDetails', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);

				if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
				{
					
					echo '<br /><b>Stuff to store in database :</b><br />';
					echo '<pre>';
					echo '<pre>';
					print_r($httpParsedResponseAr);
					echo '</pre>';
				} else  {
					echo '<div style="color:red"><b>GetTransactionDetails failed:</b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
					echo '<pre>';
					print_r($httpParsedResponseAr);
					echo '</pre>';

				}
	}else{
			echo '<div style="color:red"><b>Error : </b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
			echo '<pre>';
			print_r($httpParsedResponseAr);
			echo '</pre>';
	}
}