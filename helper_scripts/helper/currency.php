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

########################################################################
# This file enables buying currency in the client.
#
# For this to work, the all clients using currency need to add
#
#				-helperURI <WebpathToThisDirectory>
#
# to the commandline parameters when starting the client!
#
# Example:
#	client.exe -loginuri http://foo.com:8002/ -helperuri http://foo.com/
#
# Don't forget to change the currency conversion value in the wi_economy_money
# table!
#
# This requires PHP curl, XMLRPC, and MySQL extensions.
#
# If placed in the opensimwiredux web directory, it will share the db module
#


########################################################################
#
# Modified by Fumi.Iseki for XoopenSim/Modlos
# Modified by Adil El Farissi for the BTCPay integration.
#

if (!defined('ENV_READ_CONFIG')) require_once(realpath(dirname(__FILE__).'/../include/config.php'));
require_once(realpath(ENV_HELPER_PATH.'/helpers.php'));


#
# The XMLRPC server object
#
$xmlrpc_server = xmlrpc_server_create();


#
# Viewer retrieves currency buy quote
#
xmlrpc_server_register_method($xmlrpc_server, "getCurrencyQuote", "get_currency_quote");

function get_currency_quote($method_name, $params, $app_data)
{
	$req	   = $params[0];
	$agentid   = $req['agentId'];
	$secureid  = $req['secureSessionId'];
	$amount	   = $req['currencyBuy'];
	$ipAddress = $_SERVER['REMOTE_ADDR'];

	if (DISABLE_CURRENCY_PURCHASE) {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\nThe currency purchases are disabled by the administration for maintenance.\nPlease try again in some minutes... Thanks for your patience."));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

	if ($amount < LOCAL_CURRENCY_MIN_AMOUNT) {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\nThe minimum amount to  purchase is ". LOCAL_CURRENCY_MIN_AMOUNT ." ". LOCAL_CURRENCY_SYMBOL));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

	$ret = opensim_check_secure_session($agentid, null, $secureid);
	
	if ($ret) {
		$confirmvalue = get_confirm_value($ipAddress);
		$cost = convert_to_real($amount, false);
		if($cost != null){
			if(strpos($_SERVER["HTTP_USER_AGENT"],"Firestorm-Release") > -1){
				$currency = array('estimatedLocalCost'=> $cost['fiatAmount'] ." ". BTCPAY_FIAT. "  ~  ". $cost['cryptoAmount'] ." ". BTCPAY_PAYMENT_CRYPTOCURRENCY, 'currencyBuy'=> $amount);
			}else{
				$currency = array('estimatedCost'=> $cost['fiatAmount'], 'currencyBuy'=> $amount);
			}
			$response_xml = xmlrpc_encode(array('success'	=> True, 
													'currency'	=> $currency, 
													'confirm'	=> $confirmvalue));
			
		}
		else{
			$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\nFailed to get the rates from the data source... Please, wait a minute and try again.\n"));
		}
	}
	else {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "Unable to Authenticate\n\nClick URL for more info.",
											'errorURI'	  => "".SYSURL.""));
	}

	header("Content-type: text/xml");
	echo $response_xml;
	return "";
}

#
# Viewer buys currency
#
xmlrpc_server_register_method($xmlrpc_server, "buyCurrency", "buy_currency");

function buy_currency($method_name, $params, $app_data)
{
	$req	   = $params[0];
	$agentid   = $req['agentId'];
	$secureid  = $req['secureSessionId'];
	$amount	   = $req['currencyBuy'];
	$confim	   = $req['confirm'];
	$ipAddress = $_SERVER['REMOTE_ADDR'];

	if (DISABLE_CURRENCY_PURCHASE) {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\n\nThe currency purchases are disabled by the administration for maintenance.\nPlease try again in some minutes... Thanks for your patience."));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

	if ($confim!=get_confirm_value($ipAddress)) {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\n\nMissmatch Confirm Value!",
											'errorURI'	  => "".SYSURL.""));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

	$checkSecure = opensim_check_secure_session($agentid, null, $secureid);
	if (!$checkSecure) {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\n\nMissmatch Secure Session ID!!",
											'errorURI'	  => "".SYSURL.""));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

	if ($amount < LOCAL_CURRENCY_MIN_AMOUNT) {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\n\nThe minimum amount to  purchase is". LOCAL_CURRENCY_MIN_AMOUNT ." ". LOCAL_CURRENCY_SYMBOL));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}

	$invoiceData = null;
	$pendingInvoice = hasPendingInvoice($agentid);
	if($pendingInvoice != null){
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\n\nYou have an invoice in wait of your payment!. Please, open the invoice page to process your payment:\n". BTCPAY_SERVER_URL ."/i/". $pendingInvoice ."\n or wait the expiration of the pending invoice to be able to buy ". LOCAL_CURRENCY_SYMBOL,
											'errorURI'	  => "". BTCPAY_SERVER_URL ."/i/". $pendingInvoice .""));
		header("Content-type: text/xml");
		echo $response_xml;
		return "";
	}
	else{
		$invoiceData = requestInvoice($agentid, $secureid, $amount, false);
	}

	if ($invoiceData != null) {
		if(array_key_exists('errorMessage', $invoiceData)){
			$response_xml = xmlrpc_encode(array('success' => False,
											'errorMessage'=> "BTCPay error: ". $invoiceData["errorMessage"]));
											header("Content-type: text/xml");
											echo $response_xml;
											return "";

		}
		if( array_key_exists('storeId', $invoiceData)
			&& $invoiceData["storeId"] == BTCPAY_STORE_ID
			&& $invoiceData["id"] != null
			&& $invoiceData["metadata"]["posData"]["localAmount"] == $amount
			&& $invoiceData["metadata"]["posData"]["avatartId"] == $agentid
			&& $invoiceData["metadata"]["posData"]["txType"] == "5010"
			){
				$message = "Thank you for purchasing ". $amount ." ". LOCAL_CURRENCY_SYMBOL .".\nYour invoice ID is:\n". $invoiceData['id'] ."\nPlease, open the invoice page to process your payment...\n" ;
				
				loadBTCPayURL($agentid, BTCPAY_SERVER_URL ."/i/". $invoiceData["id"], $message, "BTCPay Invoice", $secureid);
				
				$response_xml = xmlrpc_encode(array('success' => True));
				header("Content-type: text/xml");
				echo $response_xml;
				return "";
		}
		else{
			$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\nUnable to process the transaction. The invoice request failed!"
											));
											header("Content-type: text/xml");
											echo $response_xml;
											return "";

		}
	}
	else {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "\nUnable to process the transaction. The gateway denied your charge"
											));
											header("Content-type: text/xml");
											echo $response_xml;
											return "";

	}

	header("Content-type: text/xml");
	echo $response_xml;
	return "";
}


#
# Region requests account balance
#
xmlrpc_server_register_method($xmlrpc_server, "simulatorUserBalanceRequest", "balance_request");

function balance_request($method_name, $params, $app_data)
{
	$req	  = $params[0];
	$agentid  = $req['agentId'];
	$secureid = $req['secureSessionId'];

	$balance = get_balance($agentid, $secureid);

	if ($balance>=0) {
		$response_xml = xmlrpc_encode(array('success' => True,
											'agentId' => $agentid,
											'funds'   => $balance));
	}
	else {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "Could not authenticate your avatar. Money operations may be unavailable",
											'errorURI'	  => " "));
	}

	header("Content-type: text/xml");
	echo $response_xml;

	return "";
}


#
# Region initiates money transfer (Direct DB Operation for security)
#
xmlrpc_server_register_method($xmlrpc_server, "regionMoveMoney", "region_move_money");

function region_move_money($method_name, $params, $app_data)
{
	$req					= $params[0];
	$agentid				= $req['agentId'];
	$destid					= $req['destId'];
	$secureid				= $req['secureSessionId'];
	$regionid				= $req['regionId'];
	$secret					= $req['secret'];
	$currencySecret			= $req['currencySecret'];
	$cash					= $req['cash'];
	$aggregatePermInventory = $req['aggregatePermInventory'];
	$aggregatePermNextOwner = $req['aggregatePermNextOwner'];
	$flags				 	= $req['flags'];
	$transactiontype		= $req['transactionType'];
	$description			= $req['description'];
	$ipAddress			  	= $_SERVER['REMOTE_ADDR'];

	$ret = opensim_check_region_secret($regionid, $secret);

	if ($ret) {
		$ret = opensim_check_secure_session($agentid, $regionid, $secureid);

		if ($ret) {
			$balance = get_balance($agentid, $secureid);
			if ($balance >= $cash) {
				move_money($agentid, $destid, $cash, $transactiontype, $flags, $description, 
										$aggregatePermInventory, $aggregatePermNextOwner, $ipAddress);
				$sbalance = get_balance($agentid, $secureid);
				$dbalance = get_balance($destid);

				$response_xml = xmlrpc_encode(array('success'		=> True,
													'agentId'		=> $agentid,
													'funds'		  	=> $balance,
													'funds2'		=> $balance,
													'currencySecret'=> " "));

				update_simulator_balance($agentid, $sbalance, $secureid);
				update_simulator_balance($destid,  $dbalance);
			}
			else {
				$response_xml = xmlrpc_encode(array('success'	  => False,
													'errorMessage'=> "You do not have sufficient funds for this purchase",
													'errorURI'	  => " "));
			}
		}
		else {
			$response_xml = xmlrpc_encode(array('success'	  => False,
												'errorMessage'=> "Unable to authenticate avatar. Money operations may be unavailable",
												'errorURI'	  => " "));
		}
	}
	else {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "This region is not authorized to manage your money.",
											'errorURI'	  => " "));
	}

	header("Content-type: text/xml");
	echo $response_xml;

	return "";
}


#
# Region claims user
#
xmlrpc_server_register_method($xmlrpc_server, "simulatorClaimUserRequest", "claimUser_func");

function claimUser_func($method_name, $params, $app_data)
{
	$req	  = $params[0];
	$agentid  = $req['agentId'];
	$secureid = $req['secureSessionId'];
	$regionid = $req['regionId'];
	$secret	  = $req['secret'];
	
	$ret = opensim_check_region_secret($regionid, $secret);

	if ($ret) {
		$ret = opensim_check_secure_session($agentid, null, $secureid);

		if ($ret) {
			$ret = opensim_set_current_region($agentid, $regionid);

			if ($ret) {
				$balance = get_balance($agentid, $secureid);
				$response_xml = xmlrpc_encode(array('success'		=> True,
													'agentId'		=> $agentid,
													'funds'		    => $balance,
													'currencySecret'=> " "));
			}
			else {
				$response_xml = xmlrpc_encode(array('success'	  => False,
													'errorMessage'=> "Error occurred, when DB was updated.",
													'errorURI'	  => " "));
			}
		}
		else {
			$response_xml = xmlrpc_encode(array('success'	  => False,
												'errorMessage'=> "Unable to authenticate avatar. Money operations may be unavailable.",
												'errorURI'	  => " "));
		}
	}
	else {
		$response_xml = xmlrpc_encode(array('success'	  => False,
											'errorMessage'=> "This region is not authorized to manage your money.",
											'errorURI'	  => " "));
	}

	header("Content-type: text/xml");
	echo $response_xml;
	
	return "";
}



#
# Process the request
#
if (!isset($HTTP_RAW_POST_DATA)) $HTTP_RAW_POST_DATA = file_get_contents('php://input');
$request_xml = $HTTP_RAW_POST_DATA;
//error_log('currency.php: '.$request_xml);

xmlrpc_server_call_method($xmlrpc_server, $request_xml, '');
xmlrpc_server_destroy($xmlrpc_server);

