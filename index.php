<?php
header('Content-Type: text/html; charset=utf-8');

//توکن درگاه ایرپول
$token = '8f90d8b359c6bd1a11973c7b3c2803bb';
$test_mode = true;
$script_url = "http://127.0.0.1/GitHub/irpul/sample_code";

ini_set("display_errors", 1);
error_reporting(E_ALL); 

//خرید
if(isset($_POST['start_pay'])){ 
	$parameters = array( 
		'method' 		=> 'payment', 				// Required
		'amount' 		=> $_POST['amount'], 		// Required - (Rial)
		'callback_url' 	=> $_POST['callback_url'], 	// Required
		'order_id' 		=> $_POST['order_id'], 		// Optional
		'product' 		=> $_POST['product'],		// Required
		'payer_name' 	=> $_POST['payer_name'],	// Required
		'phone' 		=> $_POST['phone'],			// Required
		'mobile' 		=> $_POST['mobile'],		// Required
		'email'			=> $_POST['email'],			// Optional
		'address' 		=> $_POST['address'],		// Optional
		'description' 	=> $_POST['description'],	// Optional
		'test_mode' 	=> $test_mode				// Optional
	);
	
	$result 	= post_data('https://irpul.ir/ws.php', $parameters, $token );

	if( isset($result['http_code']) ){
		$data =  json_decode($result['data'],true);

		if( isset($data['code']) && $data['code'] === 1){
			header("Location: " . $data['url']);
			exit;
		}
		else{
			echo "Error Code: ".$data['code'] . ' ' . $data['status'];
		}
	}else{
		echo 'پاسخی از سرویس دهنده دریافت نشد. لطفا دوباره تلاش نمائید';
	}
}
elseif( isset($_GET['callback']) && isset($_POST['trans_id']) && isset($_POST['order_id']) && isset($_POST['amount']) && isset($_POST['refcode']) && isset($_POST['status']) ){ //تائید تراکنش
	$trans_id 	= $_POST['trans_id'];
	$order_id 	= $_POST['order_id'];
	$amount 	= $_POST['amount'];
	$refcode	= $_POST['refcode'];
	$status 	= $_POST['status'];
	
	if($status == 'paid'){
		$parameters = array	(
			'method' 	    => 'verify',
			'trans_id' 		=> $trans_id,	// Required
			'amount'	 	=> $amount		// Required
		);
		
		$result =  post_data('https://irpul.ir/ws.php', $parameters, $token );

		if( isset($result['http_code']) ){
			$data =  json_decode($result['data'],true);
			$irpul_amount  = $data['amount'];

			if( isset($data['code']) && $data['code'] === 1){
				if($amount == $irpul_amount){
					echo "transaction paid. refcode :" .$refcode;
				}
				else{
					$error_msg ='مبلغ تراکنش در ایرپول (' . number_format($irpul_amount) . ' تومان) تومان با مبلغ تراکنش ارسالی (' . number_format($amount) . ' تومان) برابر نیست';
				}
			}
			else{
				echo 'خطا در پرداخت. کد خطا: ' . $data['code'] . '<br/>' . $data['status'];
				echo "<br/><a href='http://irpul.ir/webservice/' target='_blank' >Help</a>";
			}
		}else{
			echo 'پاسخی از سرویس دهنده دریافت نشد. لطفا دوباره تلاش نمائید';
		}
	}else{
		echo "تراکنش پرداخت نشده است";
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
			<td><input type=" text" name="callback_url" value="<?php echo $script_url; ?>/index.php?callback" /></td>
		</tr>
		<tr>
			<th></th>
			<td><input type="submit" name="start_pay" value="pay now" /></td>
		</tr>
	</table>
</form>
<?php } 

function post_data($url,$params,$token) {
	ini_set('default_socket_timeout', 15);

	$headers = array(
		"Authorization: token= {$token}",
		'Content-type: application/json'
	);

	$handle = curl_init($url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($handle, CURLOPT_TIMEOUT, 40);
	curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($params) );
	curl_setopt($handle, CURLOPT_HTTPHEADER, $headers );

	$response = curl_exec($handle);
	//error_log('curl response1 : '. print_r($response,true));

	$msg='';
	$http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));

	$status= true;

	if ($response === false) {
		$curl_errno = curl_errno($handle);
		$curl_error = curl_error($handle);
		$msg .= "Curl error $curl_errno: $curl_error";
		$status = false;
	}

	curl_close($handle);//dont move uppder than curl_errno

	if( $http_code == 200 ){
		$msg .= "Request was successfull";
	}
	else{
		$status = false;
		if ($http_code == 400) {
			$status = true;
		}
		elseif ($http_code == 401) {
			$msg .= "Invalid access token provided";
		}
		elseif ($http_code == 502) {
			$msg .= "Bad Gateway";
		}
		elseif ($http_code >= 500) {// do not wat to DDOS server if something goes wrong
			sleep(2);
		}
	}

	$res['http_code'] 	= $http_code;
	$res['status'] 		= $status;
	$res['msg'] 		= $msg;
	$res['data'] 		= $response;

	if(!$status){
		//error_log(print_r($res,true));
	}
	return $res;
}
?>



