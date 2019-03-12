<?php
session_start();
include_once("paypal.class.php");
$paypalmode = ($PayPalMode=='sandbox') ? '.sandbox' : '';

if($_SESSION['product'])
{
	//Other important variables like tax, shipping cost
	$TotalTaxAmount 	= 2.58;  //Sum of tax for all items in this order. 
	$HandalingCost 		= 2.00;  //Handling cost for this order.
	$InsuranceCost 		= 1.00;  //shipping insurance cost for this order.
	$ShippinDiscount 	= -3.00; //Shipping discount for this order. Specify this as negative number.
	$ShippinCost 		= 3.00; //Although you may change the value later, try to pass in a shipping amount that is reasonably accurate.

	//we need 4 variables from product page Item Name, Item Price, Item Number and Item Quantity.
	//Please Note : People can manipulate hidden field amounts in form,
	//In practical world you must fetch actual price from database using item id. 
	//eg : $ItemPrice = $mysqli->query("SELECT item_price FROM products WHERE id = Product_Number");
	$paypal_data ='';
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

		//create items for session
		$paypal_product['items'][] = array('itm_name'=>$productList['productName'],
											'itm_price'=>$productList['productPrice'],
											'itm_code'=>$productList['productCode'][$key], 
											'itm_qty'=>$productList['productQty'][$key]
											);
  	}
				
	//Grand total including all tax, insurance, shipping cost and discount
	$GrandTotal = ($ItemTotalPrice + $TotalTaxAmount + $HandalingCost + $InsuranceCost + $ShippinCost + $ShippinDiscount);
	
								
	$paypal_product['assets'] = array('tax_total'=>$TotalTaxAmount, 
								'handaling_cost'=>$HandalingCost, 
								'insurance_cost'=>$InsuranceCost,
								'shippin_discount'=>$ShippinDiscount,
								'shippin_cost'=>$ShippinCost,
								'grand_total'=>$GrandTotal
							);
	
	//create session array for later use
	$_SESSION["paypal_products"] = $paypal_product;
	
	//Parameters for SetExpressCheckout, which will be sent to PayPal
	$padata = 	'&METHOD=SetExpressCheckout'.
				'&RETURNURL='.urlencode($PayPalReturnURL ).
				'&CANCELURL='.urlencode($PayPalCancelURL).
				'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").
				$paypal_data.				
				'&NOSHIPPING=0'. //set 1 to hide buyer's shipping address, in-case products that does not require shipping
				'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($ItemTotalPrice).
				'&PAYMENTREQUEST_0_TAXAMT='.urlencode($TotalTaxAmount).
				'&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($ShippinCost).
				'&PAYMENTREQUEST_0_HANDLINGAMT='.urlencode($HandalingCost).
				'&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($ShippinDiscount).
				'&PAYMENTREQUEST_0_INSURANCEAMT='.urlencode($InsuranceCost).
				'&PAYMENTREQUEST_0_AMT='.urlencode($GrandTotal).
				'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($PayPalCurrencyCode).
				'&LOCALECODE=GB'. //PayPal pages to match the language on your website.
				'&LOGOIMG=http://www.sanwebe.com/wp-content/themes/sanwebe/img/logo.png'. //site logo
				'&CARTBORDERCOLOR=FFFFFF'. //border color of cart
				'&ALLOWNOTE=1';
		
		//We need to execute the "SetExpressCheckOut" method to obtain paypal token
		$paypal= new MyPayPal();
		$httpParsedResponseAr = $paypal->PPHttpPost('SetExpressCheckout', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);
		
		//Respond according to message we receive from Paypal
		if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
		{
				//Redirect user to PayPal store with Token received.
			 	$paypalurl ='https://www'.$paypalmode.'.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$httpParsedResponseAr["TOKEN"].'';
				header('Location: '.$paypalurl);
		}
		else
		{
			//Show error message
			echo '<div style="color:red"><b>Error : </b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
			echo '<pre>';
			print_r($httpParsedResponseAr);
			echo '</pre>';
			
		}

}

?>
