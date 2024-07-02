<?php
# 
#  Copyright (c)Melanie Thielker and Teravus Ovares (http://opensimulator.org/)
#
#  Redistribution and use in source and binary forms, with or without
#  modification, are permitted provided that the following conditions are met:
#	  * Redistributions of source code must retain the above copyright
#		notice, this list of conditions and the following disclaimer.
#	  * Redistributions in binary form must reproduce the above copyright
#		notice, this list of conditions and the following disclaimer in the
#		documentation and/or other materials provided with the distribution.
#	  * Neither the name of the OpenSim Project nor the
#		names of its contributors may be used to endorse or promote products
#		derived FROM this software without specific prior written permission.
# 
#  THIS SOFTWARE IS PROVIDED BY THE DEVELOPERS ``AS IS'' AND ANY
#  EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
#  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
#  DISCLAIMED. IN NO EVENT SHALL THE CONTRIBUTORS BE LIABLE FOR ANY
#  DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
#  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
#  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
#  ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
#  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
#  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
# 
# Modified by Adil El Farissi for the BTCPay integration.

if (!defined('ENV_READ_CONFIG')) require_once(realpath(dirname(__FILE__).'/../include/config.php'));
require_once(realpath(ENV_HELPER_PATH.'/../include/opensim.mysql.php'));

if (!isset($HTTP_RAW_POST_DATA)) $HTTP_RAW_POST_DATA = file_get_contents('php://input');
#$request_xml = $HTTP_RAW_POST_DATA;
#error_log('helper.php: '.$request_xml);

###################### No user serviceable parts below #####################

#
# Helper routines
#

# This function convert in real time the local currency amount to fiat and crypto 
# amounts using BTCPay as rates provider.
# You can setup the rates source in your BTCPay Store Settings > Rates.
function  convert_to_real($amount, $isWithdrawal)
{
	if($amount == 0) return 0;
	$fiatAmount = $isWithdrawal 
				? bcmul(LOCAL_CURRENCY_WITHDRAWAL_PRICE, $amount, 2) 
				: bcmul(LOCAL_CURRENCY_PRICE, $amount, 2);
	$url = BTCPAY_SERVER_URL .'/api/rates?storeId='. BTCPAY_STORE_ID .'&currencyPairs='. BTCPAY_PAYMENT_CRYPTOCURRENCY .'_'. BTCPAY_FIAT;
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	if (DISABLE_SSL_VERIFICATION){
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}

	$rateJson = curl_exec($ch);	   
	curl_close($ch);

	$rateData = null;
	$cost = null;
	$rateData = json_decode($rateJson, true);
	if($rateData && $rateData[0]['rate'] != null && !array_key_exists('error',$rateData)){
		$rate = $rateData[0]['rate'];
		$cryptoAmount = bcdiv($fiatAmount, $rate, 8);
		$cost = array(
			'fiatAmount' => $fiatAmount,
			'cryptoAmount' => $cryptoAmount,
			'rate' => $rate);
	}
	else{
		$cost = null;
	}
	return $cost;
}

/*
下記関数は現在のところ，アバターがログインしていないと使用できない

 function  user_alert($agentID, $message, $secureID=null)
 function  update_simulator_balance($agentID, $amount=-1, $secureID=null)
 function  add_money($agentID, $amount, $secureID=null) 
 function  get_balance($agentID, $secureID=null)
 function  move_money($srcID, $dstID, $amount, $type, $desc, $prminvent=0, $nxtowner=0, $ip='')
*/

//
// アバターがログインしていないと使用できない
//

function  user_alert($agentID, $message, $secureID=null)
{
	if (!USE_CURRENCY_SERVER) 	  return false;
	if (!isGUID($agentID)) 		  return false;
	if (!isGUID($secureID, true)) return false;

	// XML RPC to Region Server
	$results = opensim_get_userinfo($agentID);
	$url = parse_url($results['simip']);
	$port = $url['scheme'] == "https" ? 7001 : 7000;
	$server = make_url($results['simip'], $port);
	if ($server['host']=='') return false;
	
	$results = opensim_get_avatar_session($agentID);		// use Presence Table
	if (!$results) return false;
	$sessionID = $results['sessionID'];
	if ($secureID==null) $secureID = $results['secureID'];

	$req 	  = array('clientUUID'=>$agentID, 'clientSessionID'=>$sessionID, 'clientSecureSessionID'=>$secureID, 'Description'=>$message); 
	$params   = array($req);
	$request  = xmlrpc_encode_request('UserAlert', $params);
	$response = do_call($server['url'], $server['port'], $request);

	if ($response!=null and array_key_exists('success', $response)) return $response['success'];
	return false;
}

function  loadBTCPayURL($agentID, $invoiceUrl, $message, $type, $secureID=null)
{
	if (!USE_CURRENCY_SERVER) 	  return false;
	if (!isGUID($agentID)) 		  return false;
	if (!isGUID($secureID, true)) return false;

	// XML RPC to Region Server
	$results = opensim_get_userinfo($agentID);
	$url = parse_url($results['simip']);
	$port = $url['scheme'] == "https" ? 7001 : 7000;
	$server  = make_url($results['simip'], $port);
	if ($server['host']=='') return false;
	
	$results = opensim_get_avatar_session($agentID);		// use Presence Table
	if (!$results) return false;
	$sessionID = $results['sessionID'];
	if ($secureID==null) $secureID = $results['secureID'];

	$req 	  = array('clientUUID'=>$agentID, 'clientSessionID'=>$sessionID, 'clientSecureSessionID'=>$secureID, 'Description'=>$message, 'Url'=>$invoiceUrl, 'Type'=> $type); 
	$params   = array($req);
	$request  = xmlrpc_encode_request('LoadBTCPayURL', $params);
	$response = do_call($server['url'], $server['port'], $request);

	if ($response!=null and array_key_exists('success', $response)) return $response['success'];
	return false;
}

function  sendWithdrawalLinkRequest($agentID, $hash)
{
	if (!USE_CURRENCY_SERVER) 	  return false;
	if (!isGUID($agentID)) 		  return false;


	// XML RPC to Region Server
	$results = opensim_get_userinfo($agentID);
	$url = parse_url($results['simip']);
	$port = $url['scheme'] == "https" ? 7001 : 7000;
	$server  = make_url($results['simip'], $port);
	if ($server['host']=='') return false;
	
	$results = opensim_get_avatar_session($agentID);		// use Presence Table
	if (!$results) return false;
	$sessionID = $results['sessionID'];
	$secureID = $results['secureID'];

	$req 	  = array('clientUUID'=>$agentID, 'clientSessionID'=>$sessionID, 'clientSecureSessionID'=>$secureID, 'Hash'=>$hash); 
	$params   = array($req);
	$request  = xmlrpc_encode_request('LoadWithdrawalLink', $params);
	$response = do_call($server['url'], $server['port'], $request);

	if ($response!=null && array_key_exists('success', $response)) return $response['success'];
	return false;
}

//
// アバターがログインしていないと使用できない
//
function  update_simulator_balance($agentID, $amount=-1, $secureID=null)
{
	if (!USE_CURRENCY_SERVER) 	  return false;
	if (!isGUID($agentID)) 		  return false;
	if (!isGUID($secureID, true)) return false;

	if ($amount<0) {
		$amount = get_balance($agentID, $secureID);
		if ($amount<0) return false;
	}

	// XML RPC to Region Server
	$results = opensim_get_userinfo($agentID);
	$url = parse_url($results['simip']);
	$port = $url['scheme'] == "https" ? 7001 : 7000;
	$server  = make_url($results['simip'], $port);
	if ($server['host']=='') return false;

	$results = opensim_get_avatar_session($agentID);
	if (!$results) return false;
	$sessionID = $results['sessionID'];
	if ($secureID == null) $secureID = $results['secureID'];

	$req	  = array('clientUUID'=>$agentID, 'clientSessionID'=>$sessionID, 'clientSecureSessionID'=>$secureID, 'Balance'=>$amount);
	$params   = array($req);
	$request  = xmlrpc_encode_request('UpdateBalance', $params);
	$response = do_call($server['url'], $server['port'], $request);

	if ($response!=null and array_key_exists('success', $response)) return $response['success'];
	return false;
}


//
// アバターがログインしていないと使用できない
//
function  add_money($agentID, $amount, $secureID=null) 
{
	if (!USE_CURRENCY_SERVER) 	  return false;
	if (!isGUID($agentID)) 		  return false;
	if (!isGUID($secureID, true)) return false;

	// XML RPC to Region Server
	$results = opensim_get_userinfo($agentID);
	$url = parse_url($results['simip']);
	$port = $url['scheme'] == "https" ? 7001 : 7000;
	$server  = make_url($results['simip'], $port);
	if ($server['host']=='') return false;

	$results = opensim_get_avatar_session($agentID);
	$sessionID = $results['sessionID'];
	if ($secureID==null) $secureID = $results['secureID'];
	
	$req	  = array('clientUUID'=>$agentID, 'clientSessionID'=>$sessionID, 'clientSecureSessionID'=>$secureID, 'amount'=>$amount);
	$params   = array($req);
	$request  = xmlrpc_encode_request('AddBankerMoney', $params);
	$response = do_call($server['url'], $server['port'], $request);

	if ($response!=null and array_key_exists('success', $response)) return $response['success'];
	return false;
}


//
// アバターがログインしていないと使用できない
//
function  get_balance($agentID, $secureID=null)
{
	$cash = -1;
	if (!USE_CURRENCY_SERVER) 	  return (integer)$cash;
	if (!isGUID($agentID)) 		  return (integer)$cash;
	if (!isGUID($secureID, true)) return (integer)$cash;

	// XML RPC to Region Server
	$results = opensim_get_userinfo($agentID);
	$url = parse_url($results['simip']);
	$port = $url['scheme'] == "https" ? 7001 : 7000;
	$server  = make_url($results['simip'], $port);
	if ($server['host']=='') return (integer)$cash;

	$results = opensim_get_avatar_session($agentID);
	$sessionID = $results['sessionID'];
	if ($sessionID=='')  return (integer)$cash;
	if ($secureID==null) $secureID = $results['secureID'];
	
	$req	  = array('clientUUID'=>$agentID, 'clientSessionID'=>$sessionID, 'clientSecureSessionID'=>$secureID);
	$params   = array($req);
	$request  = xmlrpc_encode_request('GetBalance', $params);
	$response = do_call($server['url'], $server['port'], $request);

	if ($response!=null and array_key_exists('balance', $response)) $cash = $response['balance'];
	return (integer)$cash;
}

//
// Send the money to avatar for bonus   by Milo
//
// XMLRPC による正式な手順による送金
// アバターが一度もログインしていない場合は，送金できない．
//
// $type: トランザクションのタイプ．デフォルトは 5003:ReferBonus
// $serverURI:  処理を行うリージョンサーバの URI （オフライン時対応）
// $secretCode: MoneyServer.ini に書かれた MoneyScriptAccessKey の値．
//
function  send_money($agentID, $amount, $type=5003, $serverURI=null, $secretCode=null, $description)
{
	if (!USE_CURRENCY_SERVER) return false;
	if (!isGUID($agentID)) 	  return false;

	// XML RPC to Region Server
	$server['url'] = null;
	if ($serverURI!=null) {
		$url = parse_url($serverURI);
		$port = $url['scheme'] == "https" ? 7001 : 7000;
		$server = make_url($serverURI, $port);
	}

	if ($server['url']==null) {
		$results = opensim_get_userinfo($agentID);
		$url = parse_url($results['simip']);
		$port = $url['scheme'] == "https" ? 7001 : 7000;
		$server  = make_url($results['simip'], $port);
	}
	if ($server['url']==null) return false;

	if ($secretCode!=null) {
		$secretCode = md5($secretCode.'_'.$server['host']);
	}
	else {
		$secretCode = get_confirm_value($server['host']);
	}

	$req 	  = array('agentUUID'=>$agentID, 'secretAccessCode'=>$secretCode, 'amount'=>$amount, 'transactionType'=>$type, 'description'=>$description);
	$params   = array($req);
	$request  = xmlrpc_encode_request('SendMoney', $params);
	$response = do_call($server['url'], $server['port'], $request);

	if ($response!=null and array_key_exists('success', $response)) return $response['success'];
	return false;
}

//
// Send the money to avatar for bonus   by Milo
//
// XMLRPC による正式な手順による送金
// アバターが一度もログインしていない場合は，送金できない．
//
// $serverURI:  処理を行うリージョンサーバの URI （オフライン時対応）
// $secretCode: MoneyServer.ini に書かれた MoneyScriptAccessKey の値．
//
function  move_money($fromID, $toID, $amount, $serverURI=null, $secretCode=null)
{
	if (!USE_CURRENCY_SERVER) return false;
	if (!isGUID($fromID)) 	  return false;
	if (!isGUID($toID))       return false;

	// XML RPC to Region Server
	$server['url'] = null;
	if ($serverURI!=null) {
		$url = parse_url($serverURI);
		$port = $url['scheme'] == "https" ? 7001 : 7000;
		$server = make_url($serverURI, $port);
	}

	if ($server['url']==null) {
		$results = opensim_get_userinfo($fromID);
		$url = parse_url($results['simip']);
		$port = $url['scheme'] == "https" ? 7001 : 7000;
		$server  = make_url($results['simip'], $port);
	}
	if ($server['url']==null) return false;

	if ($secretCode!=null) {
		$secretCode = md5($secretCode.'_'.$server['host']);
	}
	else {
		$secretCode = get_confirm_value($server['host']);
	}

	$req 	  = array('fromUUID'=>$fromID, 'toUUID'=>$toID, 'secretAccessCode'=>$secretCode, 'amount'=>$amount);
	$params   = array($req);
	$request  = xmlrpc_encode_request('MoveMoney', $params);
	$response = do_call($server['url'], $server['port'], $request);

	if ($response!=null and array_key_exists('success', $response)) return $response['success'];
	return false;
}

function getSecureId($agentID){
	$presenceData = opensim_get_avatar_session($agentID);
	return $presenceData["secureID"];
}

function getSessionId($agentID){
	$presenceData = opensim_get_avatar_session($agentID);
	return $presenceData["sessionID"];
}

function hasPendingInvoice($agentID){
	return has_pending_btcpay_invoice($agentID);

}

function updateInvoice($agentID, $invoiceID, $invoiceStatus){
	return update_btcpay_invoice_status($agentID, $invoiceID, $invoiceStatus);

}

function removeInvoice($agentID, $invoiceID){
	return remove_btcpay_invoice($agentID, $invoiceID);

}

function getPullPaymentData($pullPaymentID, $payoutID){
	return get_btcpay_pull_payment_data($pullPaymentID, $payoutID);
}

function hasPendingPayout($agentID){
	return has_pending_btcpay_pull_payment($agentID);

}

function updatePullPayment($agentID, $pullPaymentID, $payoutID, $pullPaymentStatus){
	return update_btcpay_payout_status($agentID, $pullPaymentID, $payoutID, $pullPaymentStatus);

}

function removePullPayment($agentID, $pullPaymentID, $payoutID){
	return remove_btcpay_pull_payment($agentID, $pullPaymentID, $payoutID);

}

// XML RPC
function  do_call($uri, $port, $request)
{
	$server = make_url($uri, $port);

	$header[] = 'Content-type: text/xml';
	$header[] = 'Content-length: '.strlen($request);
	
	$ch = curl_init();   
	curl_setopt($ch, CURLOPT_URL, $server['url']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

	if (DISABLE_SSL_VERIFICATION){
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}

	$data = curl_exec($ch);	   
	if (!curl_errno($ch)) curl_close($ch);

	$ret = false;
	if ($data) $ret = xmlrpc_decode($data);

	// for Debug
	/*
	ob_start();
	print_r($ret);
	$rt = ob_get_contents();
	ob_end_clean();
	error_log('[do_call] responce = '.$rt);
	*/

	return $ret;
}

/* Request the invoice from your BTCPay store. more infos here:
https://docs.btcpayserver.org/API/Greenfield/v1/#operation/Invoices_CreateInvoice
*/
function  requestInvoice($agentid, $secureid, $amount, $isWebPurchase)
{

	$header = array('Accept: application/json', 'Content-Type: application/json', 'Authorization: token '. BTCPAY_API_KEY);

	$checkout = array(
		"defaultPaymentMethod" => BTCPAY_PAYMENT_CRYPTOCURRENCY
	);
	$posData =  array(
		"avatarName" => opensim_get_avatar_name($agentid)["fullname"],
		"avatartId" => $agentid,
		"localAmount" => $amount,
		"txType" => "5010",
		"isWebPurchase" => $isWebPurchase
	);
	$metaData = array(
		"itemDesc" => $amount ." ". LOCAL_CURRENCY_SYMBOL ." Purchase.",
		"posData" => $posData
	);
	$additionalSearchTerms = array(
		opensim_get_avatar_name($agentid)["fullname"],
		 $agentid
	);

	$cost = convert_to_real($amount, false);
	if($cost == null) return null;

	$invoice =  array(
		"currency" => BTCPAY_FIAT,
		"amount" => BTCPAY_FIAT == BTCPAY_PAYMENT_CRYPTOCURRENCY ? $cost['cryptoAmount'] : $cost['fiatAmount'],
		"metadata" => $metaData,
		"checkout" => $checkout,
		"additionalSearchTerms" => $additionalSearchTerms
	);
	
	$request = json_encode($invoice);

	$url = BTCPAY_SERVER_URL ."/api/v1/stores/". BTCPAY_STORE_ID ."/invoices";
	$ch = curl_init();   
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

	if (DISABLE_SSL_VERIFICATION){
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}

	$data = curl_exec($ch);	   
	curl_close($ch);
	
	$ret = null;
	$ret = json_decode($data, true);
	if ($data && array_key_exists('id', $ret)) {
		$notification = "BTCPay Notification:\nInvoice successfully created.\nID: ". $ret["id"] ."\nand in wait of your payment at:\n\n". $ret["checkoutLink"]; 
		if(!$isWebPurchase){
			user_alert($ret["metadata"]["posData"]["avatartId"], $notification, $secureid);
		}

		add_new_btcpay_invoice($ret["metadata"]["posData"]["avatartId"], $ret["id"], "new", $amount);
		return $ret;
	}
	else if($data && array_key_exists('message', $ret)){
		$error = json_decode($data, true);
		$ret = array(
			"errorMessage" => "BTCPay Notification:\n". $error["message"]
		);
	}
	return $ret;	
}

function  requestWithrawal($agentid, $secureid, $amount, $destination)
{

	$header = array('Accept: application/json', 'Content-Type: application/json', 'Authorization: token '. BTCPAY_API_KEY);

	$cost = convert_to_real($amount, true);
	if($cost == null) return null;

	$pullPayment = array(
		"name" => BTCPAY_PAYMENT_CRYPTOCURRENCY ." Withdrawal To ". opensim_get_avatar_name($agentid)["fullname"],
		"description" => "✔️ Your withdrawal request was successfully done.<br>Note: Withdrawals are not automatic and require a human intervention. This may take some minutes up to some business days. Thank you for your patience.\n",
		"currency" => BTCPAY_FIAT,
		"amount" => BTCPAY_FIAT == BTCPAY_PAYMENT_CRYPTOCURRENCY ? $cost['cryptoAmount'] : $cost['fiatAmount'],
		"paymentMethods" => array(BTCPAY_PAYMENT_CRYPTOCURRENCY),
		"autoApproveClaims" => false,
		"expiresAt" => time() + 172800
	);

	$claim = array(
		"destination" => $destination,
		"amount" => $cost['fiatAmount'],
		"paymentMethod" => BTCPAY_PAYMENT_CRYPTOCURRENCY,
		"metadata" => array(
			"avatarName" => opensim_get_avatar_name($agentid)["fullname"],
			"avatartId" => $agentid,
			"localAmount" => $amount,
			"rate" => $cost["rate"],
			"txType" => "5013"
		)
	);

	$request = json_encode($pullPayment);

	$url = BTCPAY_SERVER_URL ."/api/v1/stores/". BTCPAY_STORE_ID ."/pull-payments";
	$ppch = curl_init();   
	curl_setopt($ppch, CURLOPT_URL, $url);
	curl_setopt($ppch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ppch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ppch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ppch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ppch, CURLOPT_POSTFIELDS, $request);

	if (DISABLE_SSL_VERIFICATION){
		curl_setopt($ppch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ppch, CURLOPT_SSL_VERIFYHOST, 0);
	}

	$pullPaymentData = curl_exec($ppch);	   
	curl_close($ppch);

	$ppRet = null;
	$ppRet = json_decode($pullPaymentData, true);
	if ($pullPaymentData && array_key_exists('id', $ppRet)) {
		$request = json_encode($claim);

		$url = BTCPAY_SERVER_URL ."/api/v1/pull-payments/". $ppRet['id'] ."/payouts";
		$clch = curl_init();   
		curl_setopt($clch, CURLOPT_URL, $url);
		curl_setopt($clch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($clch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($clch, CURLOPT_TIMEOUT, 3);
		curl_setopt($clch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($clch, CURLOPT_POSTFIELDS, $request);

		if (DISABLE_SSL_VERIFICATION){
			curl_setopt($clch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($clch, CURLOPT_SSL_VERIFYHOST, 0);
		}

		$claimData = curl_exec($clch);	   
		curl_close($clch);

		$clRet = null;
		$clRet = json_decode($claimData, true);

		if($claimData && array_key_exists('pullPaymentId', $clRet) && array_key_exists('id', $clRet) && $clRet['pullPaymentId'] == $ppRet['id']){

			add_new_btcpay_pull_payment($agentid, $clRet['pullPaymentId'], $clRet['id'], "new", $cost['fiatAmount'], $amount);
			
			$message = "Thank you for using our services!\nWithdrawal request successfully created.\nID: ". $clRet["pullPaymentId"] ."\nand in wait of the administration approval and payment.\nPlease, open this page to get your withdrawal status...\n"; 
			loadBTCPayURL($agentid, BTCPAY_SERVER_URL ."/pull-payments/". $clRet["pullPaymentId"], $message, "BTCPay Withrawal",$secureid);
			return array(
				"success" => true,
				"id" => $clRet["pullPaymentId"],
				"link" => BTCPAY_SERVER_URL ."/pull-payments/". $clRet["pullPaymentId"]
			);			
		}
		else if($claimData && array_key_exists('message', $clRet)){
			$error = json_decode($claimData, true);
			$notification = "BTCPay Notification:\nClaim request failed!\n". $error['message']; 
			//user_alert($agentid, $notification, $secureid);
			archivePullPayment($ppRet['id']);
			return $ret = array(
				"success" => false,
				"errorMessage" => $error["message"]
			);
		}
	}
	else if($pullPaymentData && array_key_exists('message', $ppRet)){
		$error = json_decode($pullPaymentData, true);
		$notification = "BTCPay Notification:\nWithrawal request failed!\n". $error['message']; 
		user_alert($agentid, $notification, $secureid);
		return $ret = array(
			"success" => false,
			"errorMessage" => $error["message"]
		);
	}
}

function archivePullPayment($pullPaymentID){
	$header = array('Accept: application/json', 'Content-Type: application/json', 'Authorization: token '. BTCPAY_API_KEY);

	$url = BTCPAY_SERVER_URL ."/api/v1/stores/". BTCPAY_STORE_ID ."/pull-payments/". $pullPaymentID;
	$ch = curl_init();   
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	if (DISABLE_SSL_VERIFICATION){
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}

	curl_exec($ch);	   
	curl_close($ch);
	return;
}

function getwithdrawalStatus(){return DISABLE_CURRENCY_WITHDRAWAL;}
function getCurrencyStatus(){return DISABLE_CURRENCY_PURCHASE;}
function getSupportedCrypto(){return BTCPAY_PAYMENT_CRYPTOCURRENCY;}
function getSupportedFiat(){return BTCPAY_FIAT;}
function getLocalCurrencySymbol(){return LOCAL_CURRENCY_SYMBOL;}
function getMinimumAmount(){return LOCAL_CURRENCY_MIN_AMOUNT;}
function getBuyPrice(){return LOCAL_CURRENCY_PRICE;}
function getWithdrawalPrice(){return LOCAL_CURRENCY_WITHDRAWAL_PRICE;}
function getPullPaymentLink(){return BTCPAY_SERVER_URL .'/pull-payments/';}
function getBankster(){return LOCAL_CURRENCY_BANKSTER;}
function getWebhookId(){return BTCPAY_WEBHOOK_ID;}
function getStoreId(){return BTCPAY_STORE_ID;}

function getLinkHash($account,$ip){
	return hash("sha256", $account."_".CURRENCY_SCRIPT_KEY."_".$ip);
}

function getHash($account,$avatarSecureId,$avatarSession,$ip){
	return hash("sha256", $account."_".$avatarSecureId."_".$avatarSession."_".CURRENCY_SCRIPT_KEY."_".$ip);
}

function getHashMac($data){
	return hash_hmac('sha256', utf8_encode($data), utf8_encode(BTCPAY_WEBHOOK_SECRET));
}

function isUUID($uuid){
    if ($uuid==null) return false;
    if (!preg_match('/^[0-9A-Fa-f]{8,8}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{12,12}$/', $uuid)) return false;
    return true;
}

function get_confirm_value($ipAddress)
{
	$key = CURRENCY_SCRIPT_KEY;
	if ($key=='') $key = '123456789';
	$confirmvalue = md5($key.'_'.$ipAddress);
	//hash("sha256",$key.'_'.$ipAddress);

	return $confirmvalue;
}

function make_url($serverURI, $portnum=0)
{
    $url  = '';
    $host = 'localhost';
    $port = 80;
    $protocol = 'http';

    if ($serverURI!=null) {
		$uri = parse_url($serverURI);
        if ($uri["scheme"] != null) {
            $protocol = $uri["scheme"];
            $host = $uri["host"];
            
            if ($uri["port"] != null) {
                $port = $uri["port"];
            }
            else {
                if ($portnum!=0) {
                    $port = $portnum;
                }
                else {
                    if      ($uri["scheme"]=='http')  $port = 80;
                    else if ($uri["scheme"]=='https') $port = 443;
                }
            }
        }

        if ($uri["scheme"] == null && $port==443) {
            $url = 'https://'.$host.':'.$port.'/';
            $protocol = 'https';
        }
        else if ($uri["scheme"] == null && $port==80) {
            $url = 'http://'.$host.'/';
            $protocol = 'http';
        }
        else {
            $url = $protocol.'://'.$host.':'.$port.'/';
        }
    }

    $server['url']  = $url;
    $server['host'] = $host;
    $server['port'] = $port;
    $server['porotocol'] = $protocol;
    
    return $server;
}
