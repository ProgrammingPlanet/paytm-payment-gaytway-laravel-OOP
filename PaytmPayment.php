<?php

namespace App\Http\Controllers\PaymentGateway\Paytm;

class PaytmPayment{

	private $config = array();

	function __construct(){	

		$env = 'TEST';	//PROD for production env

		$callbackUrl = route('payment.paytm.response');

		$refundUrl = '';	//leave blank for nothing

		$credential = [
								'TEST'	=>	[
												'key'		=>	'xxxxxxxxxxxxxxxx',
												'id'		=>	'xxxxxxxxxxxxxxxxxxxx',
												'website'	=>	'WEBSTAGING'			//same for all merchent user
								],
								'PROD'	=>	[
												'key'		=>	'xxxxxxxxxxxxxxxx',
												'id'		=>	'xxxxxxxxxxxxxxxxxxxx',
												'website'	=>	'XXXXXXX'			//merchent user specific
								]

			];

		$this->build($env,$credential,$callbackUrl,$refundUrl);

	}

	public function build($env,$credential,$callbackUrl,$refundUrl='')
	{
		header("Pragma: no-cache");
		header("Cache-Control: no-cache");
		header("Expires: 0");

		$this->config["CALLBACK_URL"] = $callbackUrl;
		$this->config["REFUND_URL"] = $refundUrl;		

		$this->config["MERCHANT_KEY"] = $credential[$env]['key'];
		$this->config["MERCHANT_MID"] = $credential[$env]['id'];
		$this->config["MERCHANT_WEBSITE"] = $credential[$env]['website'];
		$this->config["INDUSTRY_TYPE_ID"] = 'Retail';
		$this->config["CHANNEL_ID"] = 'WEB'; //WAP for app integration

		$this->config["STATUS_QUERY_NEW_URL"] = 'https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
		$this->config["STATUS_QUERY_URL"] = 'https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
		$this->config["TXN_URL"] = 'https://securegw-stage.paytm.in/theia/processTransaction';
		if ($env == 'PROD') {
			$this->config["STATUS_QUERY_NEW_URL"] = 'https://securegw.paytm.in/merchant-status/getTxnStatus';
			$this->config["STATUS_QUERY_URL"] = 'https://securegw.paytm.in/merchant-status/getTxnStatus';
			$this->config["TXN_URL"] = 'https://securegw.paytm.in/theia/processTransaction';
		}
	}

	public function genrateParamlist($amount,$orderId,$customerId)
	{
		$paramList = array();

		$paramList["TXN_AMOUNT"] = $amount;
		$paramList["ORDER_ID"] = $orderId;
		$paramList["CUST_ID"] = $customerId;

		$paramList["CALLBACK_URL"] = $this->config["CALLBACK_URL"];

		$paramList["MID"] = $this->config["MERCHANT_MID"];
		$paramList["INDUSTRY_TYPE_ID"] = $this->config["INDUSTRY_TYPE_ID"];
		$paramList["CHANNEL_ID"] = $this->config["CHANNEL_ID"];
		$paramList["WEBSITE"] = $this->config["MERCHANT_WEBSITE"];
		$checkSum = $this->getChecksumFromArray($paramList);

		return [$paramList,$checkSum,$this->config["TXN_URL"]];
	}

	public function TxnStatus($orderid)
	{
		$requestParamList = array("MID" => $this->config["MERCHANT_MID"] , "ORDERID" => $orderid);  
		
		$StatusCheckSum = $this->getChecksumFromArray($requestParamList);
		
		$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
		return $this->getTxnStatusNew($requestParamList);
	}



	public function encrypt_e($input, $ky) {
		$key   = html_entity_decode($ky);
		$iv = "@@@@&&&&####$$$$";
		$data = openssl_encrypt ( $input , "AES-128-CBC" , $key, 0, $iv );
		return $data;
	}

	public function decrypt_e($crypt, $ky) {
		$key   = html_entity_decode($ky);
		$iv = "@@@@&&&&####$$$$";
		$data = openssl_decrypt ( $crypt , "AES-128-CBC" , $key, 0, $iv );
		return $data;
	}

	public function generateSalt_e($length) {
		$random = "";
		srand((double) microtime() * 1000000);

		$data = "AbcDE123IJKLMN67QRSTUVWXYZ";
		$data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
		$data .= "0FGH45OP89";

		for ($i = 0; $i < $length; $i++) {
			$random .= substr($data, (rand() % (strlen($data))), 1);
		}

		return $random;
	}

	public function checkString_e($value) {
		if ($value == 'null')
			$value = '';
		return $value;
	}

	public function getChecksumFromArray($arrayList, $sort=1) {
		$key = $this->config["MERCHANT_KEY"];
		if ($sort != 0) {
			ksort($arrayList);
		}
		$str = $this->getArray2Str($arrayList);
		$salt = $this->generateSalt_e(4);
		$finalString = $str . "|" . $salt;
		$hash = hash("sha256", $finalString);
		$hashString = $hash . $salt;
		$checksum = $this->encrypt_e($hashString, $key);
		return $checksum;
	}
	public function getChecksumFromString($str, $key) {
		
		$salt = $this->generateSalt_e(4);
		$finalString = $str . "|" . $salt;
		$hash = hash("sha256", $finalString);
		$hashString = $hash . $salt;
		$checksum = $this->encrypt_e($hashString, $key);
		return $checksum;
	}

	public function verifychecksum_e($arrayList, $checksumvalue) {
		$arrayList = $this->removeCheckSumParam($arrayList);
		ksort($arrayList);
		$str = $this->getArray2StrForVerify($arrayList);
		$paytm_hash = $this->decrypt_e($checksumvalue, $this->config["MERCHANT_KEY"]);
		$salt = substr($paytm_hash, -4);

		$finalString = $str . "|" . $salt;

		$website_hash = hash("sha256", $finalString);
		$website_hash .= $salt;

		$validFlag = "FALSE";
		if ($website_hash == $paytm_hash) {
			$validFlag = "TRUE";
		} else {
			$validFlag = "FALSE";
		}
		return $validFlag;
	}

	public function verifychecksum_eFromStr($str, $key, $checksumvalue) {
		$paytm_hash = $this->decrypt_e($checksumvalue, $key);
		$salt = substr($paytm_hash, -4);

		$finalString = $str . "|" . $salt;

		$website_hash = hash("sha256", $finalString);
		$website_hash .= $salt;

		$validFlag = "FALSE";
		if ($website_hash == $paytm_hash) {
			$validFlag = "TRUE";
		} else {
			$validFlag = "FALSE";
		}
		return $validFlag;
	}

	public function getArray2Str($arrayList) {
		$findme   = 'REFUND';
		$findmepipe = '|';
		$paramStr = "";
		$flag = 1;	
		foreach ($arrayList as $key => $value) {
			$pos = strpos($value, $findme);
			$pospipe = strpos($value, $findmepipe);
			if ($pos !== false || $pospipe !== false) 
			{
				continue;
			}
			
			if ($flag) {
				$paramStr .= $this->checkString_e($value);
				$flag = 0;
			} else {
				$paramStr .= "|" . $this->checkString_e($value);
			}
		}
		return $paramStr;
	}

	public function getArray2StrForVerify($arrayList) {
		$paramStr = "";
		$flag = 1;
		foreach ($arrayList as $key => $value) {
			if ($flag) {
				$paramStr .= $this->checkString_e($value);
				$flag = 0;
			} else {
				$paramStr .= "|" . $this->checkString_e($value);
			}
		}
		return $paramStr;
	}

	public function redirect2PG($paramList, $key) {
		$hashString = $this->getchecksumFromArray($paramList);
		$checksum = $this->encrypt_e($hashString, $key);
	}

	public function removeCheckSumParam($arrayList) {
		if (isset($arrayList["CHECKSUMHASH"])) {
			unset($arrayList["CHECKSUMHASH"]);
		}
		return $arrayList;
	}

	public function getTxnStatus($requestParamList) {
		return $this->callAPI($this->config["STATUS_QUERY_URL"], $requestParamList);
	}

	public function getTxnStatusNew($requestParamList) {
		return $this->callNewAPI($this->config["STATUS_QUERY_NEW_URL"], $requestParamList);
	}

	public function initiateTxnRefund($requestParamList) {
		$CHECKSUM = getRefundChecksumFromArray($requestParamList,$this->config["MERCHANT_KEY"],0);	//PAYTM_MERCHANT_KEY
		$requestParamList["CHECKSUM"] = $CHECKSUM;
		return $this->callAPI($this->config["REFUND_URL"], $requestParamList);	//PAYTM_REFUND_URL
	}

	public function callAPI($apiURL, $requestParamList) {
		$jsonResponse = "";
		$responseParamList = array();
		$JsonData =json_encode($requestParamList);
		$postData = 'JsonData='.urlencode($JsonData);
		$ch = curl_init($apiURL);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                         
		'Content-Type: application/json', 
		'Content-Length: ' . strlen($postData))                                                                       
		);  
		$jsonResponse = curl_exec($ch);   
		$responseParamList = json_decode($jsonResponse,true);
		return $responseParamList;
	}

	public function callNewAPI($apiURL, $requestParamList) {
		$jsonResponse = "";
		$responseParamList = array();
		$JsonData =json_encode($requestParamList);
		$postData = 'JsonData='.urlencode($JsonData);
		$ch = curl_init($apiURL);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                         
		'Content-Type: application/json', 
		'Content-Length: ' . strlen($postData))                                                                       
		);  
		$jsonResponse = curl_exec($ch);   
		$responseParamList = json_decode($jsonResponse,true);
		return $responseParamList;
	}
	public function getRefundChecksumFromArray($arrayList, $key, $sort=1) {
		if ($sort != 0) {
			ksort($arrayList);
		}
		$str = getRefundArray2Str($arrayList);
		$salt = $this->generateSalt_e(4);
		$finalString = $str . "|" . $salt;
		$hash = hash("sha256", $finalString);
		$hashString = $hash . $salt;
		$checksum = $this->encrypt_e($hashString, $key);
		return $checksum;
	}
	public function getRefundArray2Str($arrayList) {	
		$findmepipe = '|';
		$paramStr = "";
		$flag = 1;	
		foreach ($arrayList as $key => $value) {		
			$pospipe = strpos($value, $findmepipe);
			if ($pospipe !== false) 
			{
				continue;
			}
			
			if ($flag) {
				$paramStr .= $this->checkString_e($value);
				$flag = 0;
			} else {
				$paramStr .= "|" . $this->checkString_e($value);
			}
		}
		return $paramStr;
	}
	public function callRefundAPI($refundApiURL, $requestParamList) {
		$jsonResponse = "";
		$responseParamList = array();
		$JsonData =json_encode($requestParamList);
		$postData = 'JsonData='.urlencode($JsonData);
		$ch = curl_init($apiURL);	
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_URL, $refundApiURL);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$headers = array();
		$headers[] = 'Content-Type: application/json';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  
		$jsonResponse = curl_exec($ch);   
		$responseParamList = json_decode($jsonResponse,true);
		return $responseParamList;
	}

}
