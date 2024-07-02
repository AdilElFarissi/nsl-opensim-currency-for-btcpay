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

$isDisabled = getCurrencyStatus();
$cryptoSymbol = getSupportedCrypto();
$fiatSymbol = getSupportedFiat();
$localCurrencySymbol = getLocalCurrencySymbol();
$localCurrencyMinimum = getMinimumAmount();
$localCurrencyBuyPrice = getBuyPrice();
$pullPaymentLink = getPullPaymentLink();
?>

<!DOCTYPE HTML>
<head>
<meta http-equiv="content-type"  content="text/html; charset=UTF-8">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<style>
body{background-color:#161B22;font-family:arial;}
h2, h3{text-shadow: 2px 2px 2px #000;}
#page{min-height: auto;margin:10px auto; width:500px;background-color:#0D1117;padding:10px 20px;color:#fff;border: 1px solid #010;border-radius:2px;}
#content{height:auto;min-height:490px;}
#support{position:relative;display:block;background-color:#020;color:#fff;padding:10px;border: 1px solid #010;border-radius:10px;}
hr, a{text-decoration:none;color:#51B13E;}
.support-img{font-size:18px;}
.warning-box{text-align:center;margin-bottom:20px;margin-top:50px;}
.warning-img{font-size:150px;margin-top:10px;}
.warning{font-size:22px;color:#f00;text-shadow:2px 2px 1px #000;margin-bottom:10px;}
.success{font-size:22px;color:#0f0;text-shadow:2px 2px 1px #000;margin-bottom:10px;}
.sub-warning, .sub-success{font-size:14px;color:#ff0;text-shadow:1px 1px 1px #000;margin-bottom:30px;}
.inputs{margin:5px auto;display:block;}
.inputs input{margin:5px auto; display:inline-block;font-size:24px;width:210px;text-align:center;}
.inputs .button, .button{font-size: 16px;color:#fff;border:none;border-radius: 5px;text-shadow:0px 0px 2px #000;font-weight:bold;padding:10px;text-align:center;cursor:pointer;}
.label{text-align:left;color:#8B949E;}
tr{display:block;width:490px;}
hr{color: #51B13E;}
li{margin-bottom:10px;}
.btn-red {background-color: #f56954 !important;background: -moz-linear-gradient(bottom,  #f00 0%, #370101 100%);background: -webkit-gradient(linear, bottom, top, color-stop(0%,#f00), color-stop(100%,#370101));background: -webkit-linear-gradient(bottom,  #f00 0%,#370101 100%); background: -o-linear-gradient(bottom,  #f00 0%,#370101 100%);background: -ms-linear-gradient(bottom,  #f00 0%,#370101 100%);background: linear-gradient(to bottom,  #f00 0%,#370101 100%);filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f00', endColorstr='#370101',GradientType=1 );}
.btn-green {background: #003300;background: -moz-linear-gradient(bottom,  #003300 0%, #00AE00 100%);background: -webkit-gradient(linear, bottom, top, color-stop(0%,#003300), color-stop(100%,#00AE00));background: -webkit-linear-gradient(bottom,  #003300 0%,#00AE00 100%);background: -o-linear-gradient(bottom,  #003300 0%,#00AE00 100%);background: -ms-linear-gradient(bottom,  #003300 0%,#00AE00 100%);background: linear-gradient(to top,  #003300 0%,#00AE00 100%);filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#003300', endColorstr='#00AE00',GradientType=1 );}
.btn-red:hover, .btn-green:hover{background-color: #f39c12 !important;background: -moz-linear-gradient(bottom,  #f39c12 0%, #875504 100%);background: -webkit-gradient(linear, bottom, top, color-stop(0%,#f39c12), color-stop(100%,#875504)); background: -webkit-linear-gradient(bottom,  #f39c12 0%,#875504 100%);background: -o-linear-gradient(bottom,  #f39c12 0%,#875504 100%);background: -ms-linear-gradient(bottom,  #f39c12 0%,#875504 100%);background: linear-gradient(to bottom,  #f39c12 0%,#875504 100%);filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f39c12', endColorstr='#875504',GradientType=1 );}

</style>
<title>BTCPay Currency Proccessor</title>
</head>
<body>
<div id="page">
    <div id="content">
        
        <h2 style="margin:5px 0;">Buy <?= $localCurrencySymbol ?> With <?= $cryptoSymbol ?></h2>
        <hr>
<?php
if(getenv('REQUEST_METHOD') == 'GET') {
    if ($isDisabled){ 
        echo 
        '<div class="warning-box">
            <span class="warning-img">⚠️</span><br>
            <span class="warning">'. $cryptoSymbol .' withdrawals are disabled<br> by the administration !</span><br>
            <span class="sub-warning">Please try again in some minutes...<br>Thanks for your patience.</span>
        </div>';
        return;
    }else 
    {
        $account = isset($_GET["account"]) ? $_GET["account"] : "";
        $amount = isset($_GET["amount"]) ? $_GET["amount"] : $localCurrencyMinimum;
        $price = convert_to_real($amount, false);
        if($price == null){
            echo 
            '<div class="warning-box">
                <span class="warning-img">⚠️</span><br>
                <span class="warning">BTCPay Server is not available!</span><br>
                <span class="sub-warning">Please try again in some minutes...<br>Thanks for your patience.</span><br>
            </div>';
            return;
        }else{

        echo 
        '<span>Exchange Rates: 1 '. $localCurrencySymbol .' = '. $localCurrencyBuyPrice .' '.$fiatSymbol .'</span>
        <div class="inputs">
            <form method="post"> 
                <table>
                    <tr>
                        <td >
                            <span class="label">Set the '. $localCurrencySymbol .' amount to buy:</span><br>
                            <input id="inputBalance" type="number" name="amount" placeholder="'. $localCurrencyMinimum .'" min="'. $localCurrencyMinimum.'" oninput="setTimeout(()=>{ if(this.value < '. $localCurrencyMinimum .') this.value = '. $localCurrencyMinimum .'; document.getElementById(\'inputFiat\').value = parseFloat(this.value *'. $localCurrencyBuyPrice.').toFixed(2)+\' '. $fiatSymbol.'\';}, 2000);" value='.$amount.'>
                        </td>
                            
                        <td><span style="font-size:40px"> 💱 </span></td>
                        <td>
                            <span class="label">Average price:</span><br>
                            <input id="inputFiat" type="text" name="fiat" value="'.$price["fiatAmount"].' '. $fiatSymbol .'" readOnly>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="label">Set the destination avatar\'s key:</span><br>
                            <input id="key" type="text" name="aviKey" placeholder="Your avatar UUID..." value="'. $account .'" style="width:490px;height:30px;" oninput="this.value = this.value.trim();">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span><b>Step By Step Guide:</b></span>
                            <ul style="margin-top: 10px;">
                                <li>Set the amount to purchase, the destination avatar UUID key and click [ Buy Now ].</li>
                                <li>Process your '. $cryptoSymbol .' payment using the provided payment infos in the BTCPay invoice.</li>
                                <li>The destination avatar will recive the purchased '. $localCurrencySymbol .' amount after the usual peers confirmations.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td style="float:right;">
                            <input class="button btn-green" type="submit" name="buy" value="Buy Now" style="margin-top:17px;">
                        </td>
                    </tr>
                </table>
            </form>
        </div>';  
        }
    }
}

if(getenv('REQUEST_METHOD') == 'POST') {
    
    if(!array_key_exists('aviKey', $_POST) || !isUUID($_POST['aviKey'])){
        echo 
            '<div class="warning-box">
            <span class="warning-img">⚠️</span><br>
            <span class="warning">Missing or wrong avatar UUID key!</span><br>
            <span class="sub-warning">You can get your UUID from your avatar\'s "Profile".<br>Thanks for your patience.</span><br>
            <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
            </div>';
            return;
    }
    else{

        $account = $_POST['aviKey'];
        $aviName = opensim_get_avatar_name($account)["fullname"];

            if(array_key_exists('amount', $_POST) 
                && $_POST['amount'] >= $localCurrencyMinimum){

                    $price = convert_to_real($_POST['amount'], false);
                    if($price == null){
                        echo 
                        '<div class="warning-box">
                            <span class="warning-img">⚠️</span><br>
                            <span class="warning">BTCPay Server is not available!</span><br>
                            <span class="sub-warning">Please try again in some minutes...<br>Thanks for your patience.</span><br>
                            <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
                        </div>';
                        return;
                    }else{
                        if ($account != null && $_POST['amount'] != null ){
                            $pendingInvoice = hasPendingInvoice($account);
                            if($pendingInvoice != null){
                                echo
                                '<div class="warning-box">
                                    <span class="warning-img">⚠️</span><br>
                                    <span class="warning">You have a pending invoice!</span><br>
                                    <span class="sub-warning">Please, open the invoice page to process your payment:<br><a href="'. BTCPAY_SERVER_URL .'/i/'. $pendingInvoice .'">'. BTCPAY_SERVER_URL .'/i/'. $pendingInvoice .'</a><br> or wait the expiration of the pending invoice to be able to buy '. LOCAL_CURRENCY_SYMBOL.'.<br>Thanks for your patience.</span><br>
                                    <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
                                </div>';
                                return;  
                            }else{
                            $ret = requestInvoice($account, null, $_POST['amount'], true);
                            if(!array_key_exists('errorMessage', $ret)){
                                /*echo
                                '<div class="warning-box">
                                <span class="warning-img">✔️</span><br>
                                <span class="success">Withdrawal Successfully Requested!</span><br>
                                <span class="sub-warning">Please open your tracking page to get more infos...<br><b>URL: <a class="" href="'. $ret["checkoutLink"].'" >'. $ret["checkoutLink"].'</b></span><br>
                                <input class="button" value="Go To Page" style="margin-top:30px;width:100px;background-color:darkgreen;"></a>
                                </div>';*/
                                header("Location: ".$ret['checkoutLink']);
                                exit();
                            }else{
                                echo
                                '<div class="warning-box">
                                    <span class="warning-img">⚠️</span><br>
                                    <span class="warning">Invoice request failed!</span><br>
                                    <span class="sub-warning">'.$ret["errorMessage"].'<br> Please, wait the transaction approuval...<br>Thanks for your patience.</span><br>
                                    <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
                                </div>';
                                return;  
                            }  
                        }                      
                        }else{
                            echo
                            '<div class="warning-box">
                                <span class="warning-img">⚠️</span><br>
                                <span class="warning">Missing invoice parameters!</span><br>
                                <span class="sub-warning">Please go back and try again...<br>Thanks for your patience.</span><br>
                                <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
                            </div>';
                            return;
                        }
                    }                
            }else{ 
                echo 
                '<div class="warning-box">
                    <span class="warning-img">⚠️</span><br>
                    <span class="warning">Missing or wrong '.$localCurrencySymbol.' amount!</span><br>
                    <span class="sub-warning">The amount in '.$localCurrencySymbol.' must be over the minimum '. $localCurrencyMinimum.' '.$localCurrencySymbol.'.</span><br>
                    <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
                </div>';
                return;
            } 
    }
}
?>
       <div id="support"><span>Need help?</span>&nbsp;&nbsp;<a href="#" target="_blank"><span class="support-img">👨‍🔧</span> InWorld</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="#" target="_blank"><span class="support-img">🌐</span> Website</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="#" target="_blank"><span class="support-img">✉️</span> E-Mail</a></div>
        </div> 
    </div>
    

</body>