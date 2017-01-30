<?php 

/*
 * 2017 - API BCA Simple PHP Class
 * Using Account Statements for Sample Request
 * 
 * Contact : rie.projects25@gmail.com
 */

class bca{
	private static $main_url = 'https://sandbox.bca.co.id'; // Change When Your Apps is Live
	private static $client_id = ''; // Fill With Your Client ID
	private static $client_secret = ''; // Fill With Your Client Secret ID
	private static $api_key = ''; // Fill With Your API Key
	private static $api_secret = ''; // Fill With Your API Secret Key
	private static $access_token = null;
	private static $signature = null;
	private static $timestamp = null;
	private static $corporate_id = 'BCAAPI2016'; // Fill With Your Corporate ID. BCAAPI2016 is Sandbox ID
	private static $account_number = '0201245680'; // Fill With Your Account Number. 0201245680 is Sandbox Account

	private function getToken(){
		$path = '/api/oauth/token';

		$headers = array(
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: Basic '.base64_encode(self::$client_id.':'.self::$client_secret));
		$data = array(
			'grant_type' => 'client_credentials'
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::$main_url.$path);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore Verify SSL Certificate
		curl_setopt_array($ch, array(
			CURLOPT_POST => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POSTFIELDS => http_build_query($data),
		));
		$output = curl_exec($ch);
		curl_close($ch);

		$result = json_decode($output,true);
		self::$access_token = $result['access_token'];
	}
	private function parseSignature($res){
		$explode_response = explode(',', $res);
		$explode_response_1 = explode(':', $explode_response[8]);

		self::$signature = trim($explode_response_1[1]);
	}
	private function parseTimestamp($res){
		$explode_response = explode(',', $res);
		$explode_response_1 = explode('Timestamp: ', $explode_response[3]);

		self::$timestamp = trim($explode_response_1[1]);
	}
	private function getSignature($url,$method,$data){
		$path = '/utilities/signature';

		$timestamp = date(DateTime::ISO8601);
		$timestamp = str_replace('+','.000+', $timestamp);
		$timestamp = substr($timestamp, 0,(strlen($timestamp) - 2));
		$timestamp .= ':00';
		$url_encode = $url;

		$headers = array(
			'Timestamp: '.$timestamp,
			'URI: '.$url_encode,
			'AccessToken: '.self::$access_token,
			'APISecret: '.self::$api_secret,
			'HTTPMethod: '.$method,
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::$main_url.$path);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore Verify SSL Certificate
		curl_setopt_array($ch, array(
			CURLOPT_POST => TRUE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POSTFIELDS => http_build_query($data),
		));
		$output = curl_exec($ch);
		curl_close($ch);

		$this->parseSignature($output);
		$this->parseTimestamp($output);
	}
	public function index(){
		$this->getToken();

		// Change this path to your desired API Services Path
		$path = '/banking/corporates/'.self::$corporate_id.'/accounts/'.self::$account_number.'/statements?StartDate=2016-09-01&EndDate=2016-09-01';
		$method = 'GET';
		$data = array();

		$this->getSignature($path, $method, $data);

		$headers = array(
			'X-BCA-Key: '.self::$api_key,
			'X-BCA-Timestamp: '.self::$timestamp,
			'Authorization: Bearer '.self::$access_token,
			'X-BCA-Signature: '.self::$signature,
			'Content-Type: application/json',
			'Origin: '.$_SERVER['SERVER_NAME']
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::$main_url.$path);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignore Verify SSL Certificate
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HTTPHEADER => $headers,
		));
		$output = curl_exec($ch); // This is API Response
		curl_close($ch);

		echo $output;
	}
}
?>