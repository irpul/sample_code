<?php
header('Content-Type: text/html; charset=utf-8');

$webgate_id = 50000000; // شناسه درگاه

ini_set("display_errors", 1);
error_reporting(E_ALL); 

if(isset($_POST['start_pay'])){ //خرید
	$parameters = array( 
		'webgate_id' 	=> $webgate_id, 			// Required (20 = Test Mode)
		'amount' 		=> $_POST['amount'], 		// Required - (Rial)
		'callback_url' 	=> $_POST['callback_url'], 	// Required
		'plugin' 		=> 'other',					// Optional
		'order_id' 		=> $_POST['order_id'], 		// Optional
		'product' 		=> $_POST['product'],		// Required
		'payer_name' 	=> $_POST['payer_name'],	// Required
		'phone' 		=> $_POST['phone'],			// Required
		'mobile' 		=> $_POST['mobile'],		// Required
		'email'			=> $_POST['email'],			// Optional
		'address' 		=> $_POST['address'],		// Optional
		'description' 	=> $_POST['description']	// Optional
	);

	try {
		$client = new SoapClient('https://irpul.ir/webservice.php?wsdl' , array('soap_version'=>'SOAP_1_1','cache_wsdl'=>WSDL_CACHE_NONE  ,'encoding'=>'UTF-8'));
		$result = $client->Payment($parameters);
		//print_r($result);
	}catch (Exception $e) { echo 'Error'. $e->getMessage();  }

	if( isset($result) && $result['res_code']===1 ){
		header('Location: '.$result['url']);
	}else {
		echo "Error Code: ".$result['res_code'] . ' ' . $result['status'];
	}
}
elseif( isset($_GET['callback']) && isset($_GET['irpul_token']) && $_GET['irpul_token']!=''  ){ //تائید تراکنش
	$irpul_token 	= $_GET['irpul_token'];
	$decrypted 		= url_decrypt( $irpul_token );
	if($decrypted['status']){
		parse_str($decrypted['data'], $ir_output);
		$tran_id 	= $ir_output['tran_id'];
		$order_id 	= $ir_output['order_id'];
		$amount 	= $ir_output['amount'];
		$refcode	= $ir_output['refcode'];
		$status 	= $ir_output['status'];
		
		if($status == 'paid'){
			$parameters = array	(
				'webgate_id'	=> $webgate_id,	// Required
				'tran_id' 		=> $tran_id,	// Required
				'amount'	 	=> $amount		// Required
			);
			try {
				$client = new SoapClient('http://irpul.ir/webservice.php?wsdl' , array('soap_version'=>'SOAP_1_1','cache_wsdl'=>WSDL_CACHE_NONE ,'encoding'=>'UTF-8'));
				$result = $client->PaymentVerification($parameters);
			}catch (Exception $e) { echo 'Error'. $e->getMessage();  }

			if ($result == 1){
				echo "transaction paid. refcode :" .$refcode;
			}
			else{
				echo 'Error Code: '.$result;
				echo "<br/><a href='http://irpul.ir/webservice/' target='_blank' >Help</a>";
			}
		}else{
			echo "transaction not paid yet";
		}
	}else{
		echo "token invalid";
	}
}
else{
?>
WebService:
<form action="index.php" method="post" >
	<table>
		<tr>
			<th>Amount</th>
			<td><input type=" text" name="amount" value="1000" /> * Rial</td>
		</tr>
		<tr>
			<th>Product Name</th>
			<td><input type=" text" name="product" value="خرید محصول تست" /> *</td>
		</tr>
		<tr>
			<th>Phone</th>
			<td><input type=" text" name="phone" value="021123456678" /> *</td>
		</tr>
		<tr>
			<th>address</th>
			<td><input type=" text" name="address" value="خیابان تهران" /> *</td>
		</tr>
		<tr>
			<th>Email</th>
			<td><input type=" text" name="email" value="test@example.com" /></td>
		</tr>
		<tr>
			<th>Order ID</th>
			<td><input type=" text" name="order_id" value="12345" /></td>
		</tr>
		<tr>
			<th>Payer Name</th>
			<td><input type=" text" name="payer_name" value="حسن رضایی" /></td>
		</tr>
		<tr>
			<th>Mobile</th>
			<td><input type=" text" name="mobile" value="09303184001" /></td>
		</tr>
		<tr>
			<th>Description</th>
			<td><input type=" text" name="description" value="توضیحات" /></td>
		</tr>
		<tr>
			<th>Callback URL</th>
			<td><input type=" text" name="callback_url" value="http://pay.inax.ir/index.php?callback" /></td>
		</tr>
		<tr>
			<th></th>
			<td><input type="submit" name="start_pay" value="pay now" /></td>
		</tr>
	</table>
</form>
<?php } 

function url_decrypt($string){
	$counter = 0;
	$data = str_replace(array('-','_','.'),array('+','/','='),$string);
	$mod4 = strlen($data) % 4;
	if ($mod4) {
	$data .= substr('====', $mod4);
	}
	$decrypted = base64_decode($data);
	
	$check = array('tran_id','order_id','amount','refcode','status');
	foreach($check as $str){
		str_replace($str,'',$decrypted,$count);
		if($count > 0){
			$counter++;
		}
	}
	if($counter === 5){
		return array('data'=>$decrypted , 'status'=>true);
	}else{
		return array('data'=>'' , 'status'=>false);
	}
}

?>



