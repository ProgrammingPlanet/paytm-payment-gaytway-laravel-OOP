<?php

namespace App\Http\Controllers\PaymentGateway\Paytm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Str;
use Session;

use App\Http\Controllers\PaymentGateway\Paytm\PaytmPayment;

class ExampleController extends Controller
{
	
	public function redirect(Request $request)
	{

		$amount = $request->input('amount');
		$orderId = $request->input('order_id');
		$customerId = $request->input('cust_id');

		$obj = new PaytmPayment;
		$arr = $obj->genrateParamlist($amount,$orderId,$customerId);

		Session(['paytmpayment'=>['amount'=>$amount,'orderid'=>$orderId]]);

		return view('PaymentGateway.Paytm.redirect',[
											"paramList"=>$arr[0],
											"checkSum"=>$arr[1],
											"txnurl"=>$arr[2]
										]);
	}

	public function response(Request $request)
	{

		$obj = new PaytmPayment;
		$error = '';
		
		if( $request->input('STATUS') == "TXN_SUCCESS"){

			$paramList = $request->all();
			$isValidChecksum = $obj->verifychecksum_e($paramList,$request->input('CHECKSUMHASH'));
			if($isValidChecksum){	//valid checksum
				$orig_info = session('paytmpayment');
				if($orig_info['orderid']==$request->input('ORDERID')){
					if($orig_info['amount']==$request->input('TXNAMOUNT')){

						return $this->txnStatus(new Request, $orig_info['orderid']);	//final varification

					}else
						$error = 'transaction amount not matched with orignel one.';
				}else
					$error = 'orderid Does not matched with orignel one.';
			}else{
				$error = "invalid checksum";
			}
		}else{
				$error = $request->input('RESPMSG');
		}

		echo "<center><br><h2>Somthing Went Wrong.</h2><h3>$error</h3><a href=".route('site.root').">Go Home</a></center>";
	}

	public function txnStatus(Request $request, $orderid='')
	{
		$orderid = $request->has('order_id') ? $request->input('order_id') : $orderid;
		$obj = new PaytmPayment;
		$responseParamList = $obj->TxnStatus($orderid);
		return view('PaymentGateway.Paytm.success',['params'=>$responseParamList]);

	}

}
