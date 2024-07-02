<?php
#
#  Add by Adil El Farissi for the BTCPay integration.
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
#		derived from this software without specific prior written permission.
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
if (!defined('ENV_READ_CONFIG')) require_once(realpath(dirname(__FILE__).'/../include/config.php'));
require_once(realpath(ENV_HELPER_PATH.'/helpers.php'));

if (getenv('REQUEST_METHOD') == 'POST') {
	$notification_data = file_get_contents("php://input");
    if ($_SERVER["HTTP_BTCPAY_SIG"] && explode("/", $_SERVER["HTTP_USER_AGENT"])[0] == "BTCPayServer"){
        $reqHash = str_replace("sha256=", "", $_SERVER["HTTP_BTCPAY_SIG"]);
        $localHash = getHashMac($notification_data); 

        if (hash_equals($reqHash, $localHash)){
            $data = json_decode($notification_data, true);
            if ($data["webhookId"] == getWebhookId() && $data["storeId"] == getStoreId()){
                if (array_key_exists("invoiceId", $data) && $data["metadata"] != null){
                    $agentId = $data["metadata"]["posData"]["avatartId"];
                    $secureId = getSecureId($agentId);
                    $amount = $data["metadata"]["posData"]["localAmount"];
                    $isWebPurchase = (bool)$data["metadata"]["posData"]["isWebPurchase"];
                    if ($agentId != null && $secureId != null && $amount != null){
                        if ($data["type"] == "InvoiceExpired" || $data["type"] == "InvoiceInvalid"){
                            $message = "Invoice Expired!\nID: ". $data["invoiceId"];
                            if(!$isWebPurchase){
                            user_alert($agentId, $message, $secureId);
                            }
                            removeInvoice($agentId, $data["invoiceId"]);
                            header("HTTP/1.1 200 OK");
                            return "";
                        }
                        else if ($data["type"] == "InvoiceProcessing"){
                            $message = "Invoice ID: ". $data["invoiceId"] ."\n is paid and waiting the usual confirmations";
                            if(!$isWebPurchase){
                                user_alert($agentId, $message, $secureId);
                            }
                            updateInvoice($agentId, $data["invoiceId"], "paid");
                            header("HTTP/1.1 200 OK");
                            return "";
                        }
                        else if ($data["type"] == "InvoiceSettled" && !(bool)$data["isRedelivery"]){
                            $message = "Thank you for your purchase!\nInvoice ID: ". $data["invoiceId"] ."\nis fully paid and confirmed. you have recived ". $amount ." ". getLocalCurrencySymbol() .".";
                            $description = "";
                            if ($data["metadata"]["posData"]["txType"] == "5010"){
                                $description = "Currency Buy Invoice:". $data["invoiceId"];
                            }
                            $description = "Currency Buy - Invoice: ". $data["invoiceId"];
                            if(!$isWebPurchase){
                                user_alert($agentId, $message, $secureId);
                            }
                            if (send_money($agentId, (int)$amount, 5010, null, null, $description)){
                                updateInvoice($agentId, $data["invoiceId"], "settled");
                            }
                            header("HTTP/1.1 200 OK");
                            return "";
                        }
                    }
                }
                else if (array_key_exists("pullPaymentId", $data) && array_key_exists("payoutId", $data)){
                    if ($data["type"] == "PayoutCreated" && $data["payoutState"] == "AwaitingApproval"){
                        $ppData = getPullPaymentData($data["pullPaymentId"], $data["payoutId"]);
                        
                        if ($ppData != null && $ppData["status"] == "new"){
                            updatePullPayment($ppData["agentID"], $data["pullPaymentId"], $data["payoutId"], $data["payoutState"]);
                            move_money($ppData["agentID"], getBankster(), (int)$ppData["localAmount"],null,null);                            
                            header("HTTP/1.1 200 OK");
                            return "";
                        }                        
                    }
                    else if ($data["type"] == "PayoutApproved" && $data["payoutState"] == "AwaitingPayment" && !(bool)$data["isRedelivery"]){
                        $ppData = getPullPaymentData($data["pullPaymentId"], $data["payoutId"]);
                        
                        if ($ppData != null && $ppData["status"] == "AwaitingApproval"){
                            updatePullPayment($ppData["agentID"], $data["pullPaymentId"], $data["payoutId"], $data["payoutState"]);
                            $message = "Your withrawal request\nID: ". $data["pullPaymentId"] ."\nwas approved by the administration.";
                            user_alert($ppData["agentID"], $message, getSecureId($ppData["agentID"]));
                            header("HTTP/1.1 200 OK");
                            return "";
                        }
                    }
                    else if ($data["type"] == "PayoutUpdated" && $data["payoutState"] == "InProgress" && !(bool)$data["isRedelivery"]){
                        $ppData = getPullPaymentData($data["pullPaymentId"], $data["payoutId"]);
                        
                        if ($ppData != null && $ppData["status"] == "AwaitingPayment"){
                            updatePullPayment($ppData["agentID"], $data["pullPaymentId"], $data["payoutId"], $data["payoutState"]);
                            $message = "Your withrawal request\nID: ". $data["pullPaymentId"] ."\nwas honored by the administration.\nPlease, check the incoming trasaction in your wallet.";
                            user_alert($ppData["agentID"], $message, getSecureId($ppData["agentID"]));
                            header("HTTP/1.1 200 OK");
                            return "";
                        }
                    }
                    else if ($data["type"] == "PayoutUpdated" && $data["payoutState"] == "Completed" && !(bool)$data["isRedelivery"]){
                        $ppData = getPullPaymentData($data["pullPaymentId"], $data["payoutId"]);
                        
                        if ($ppData != null && $ppData["status"] == "InProgress" || $ppData["status"] == "AwaitingPayment"){
                            updatePullPayment($ppData["agentID"], $data["pullPaymentId"], $data["payoutId"], $data["payoutState"]);
                            $message = "Your withrawal request\nID: ". $data["pullPaymentId"] ."\nwas confirmed and completed.\nThank you for using our services.";
                            user_alert($ppData["agentID"], $message, getSecureId($ppData["agentID"]));
                            archivePullPayment($data["pullPaymentId"]);
                            header("HTTP/1.1 200 OK");
                            return "";
                        }
                    }
                    else if ($data["type"] == "PayoutUpdated" && $data["payoutState"] == "Cancelled"){
                        $ppData = getPullPaymentData($data["pullPaymentId"], $data["payoutId"]);
                        
                        if ($ppData != null && $ppData["agentID"] != null){
                            removePullPayment($ppData["agentID"], $data["pullPaymentId"], $data["payoutId"]);
                            $message = "Your withrawal request\nID: ". $data["pullPaymentId"] ."\nwas rejected or cancelled by the administration.\nPlease, contact the support and provide the request ID for more infos.";
                            user_alert($ppData["agentID"], $message, getSecureId($ppData["agentID"]));
                            archivePullPayment($data["pullPaymentId"]);
                            header("HTTP/1.1 200 OK");
                            return "";
                        }
                    }
                }
            }
        }
    }
    header("HTTP/1.1 200 OK");
    return "";
}
?>