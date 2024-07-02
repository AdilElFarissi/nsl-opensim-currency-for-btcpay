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

$isDisabled = getwithdrawalStatus();
$cryptoSymbol = getSupportedCrypto();
$fiatSymbol = getSupportedFiat();
$localCurrencySymbol = getLocalCurrencySymbol();
$localCurrencyMinimum = getMinimumAmount();
$localCurrencyWithdrawalPrice = getWithdrawalPrice();
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
<title>BTCPay Withdrawals Proccessor</title>
</head>
<body>
<div id="page">
    <div id="content">
        <h2 style="margin:5px 0;">Withdraw To <?= $cryptoSymbol ?></h2>
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
    if (isset($_GET["a"]) && isset($_GET["sh"])){
        $account = $_GET["a"];
        $securityHash = $_GET["sh"];
        $session = opensim_get_avatar_session($account);
        $avatarSecureId = $session["secureID"];
        $avatarSession = $session["sessionID"];
        $tempHash = getHash($account,$avatarSecureId,$avatarSession,$_SERVER['REMOTE_ADDR']);
        $aviName = opensim_get_avatar_name($account)["fullname"];
        $balance = get_balance($account, $avatarSecureId);
        $price = convert_to_real($balance, true);      
        $pp = hasPendingPayout($account);
        if(hash_equals($securityHash, $tempHash)){
            if ($price == null){ 
                echo 
                '<div class="warning-box">
                    <span class="warning-img">⚠️</span><br>
                    <span class="warning">BTCPay Server is not available!</span><br>
                    <span class="sub-warning">Please try again in some minutes...<br>Thanks for your patience.</span>
                </div>';
                return;
            }else if($pp != null){
                echo 
                '<div class="warning-box">
                    <span class="warning-img">⚠️</span><br>
                    <span class="warning">You have a pending withdrawal request!</span><br>
                    <span class="sub-warning">Please, wait the administration approuval to:<br><a href="'.$pullPaymentLink. $pp .'">'.$pullPaymentLink. $pp .'</a><br>Thanks for your patience.</span>
                </div>';
                return;
            }
            else { 
                echo 
                '<span>Welcome back <b>'. $aviName .'</b> ✔️</span><br>
                <span>Your Balance is: <b>'.$balance.' '.$localCurrencySymbol .'&nbsp;&nbsp;=&nbsp;&nbsp;'. $price["fiatAmount"].' '. $fiatSymbol .'&nbsp;&nbsp;~&nbsp;&nbsp;'. $price["cryptoAmount"].' '. $cryptoSymbol .'</b></span><br>
                <span>Exchange Rates: 1 '. $localCurrencySymbol .' = '. $localCurrencyWithdrawalPrice .' '.$fiatSymbol .'&nbsp;&nbsp;|&nbsp;&nbsp;1 '. $cryptoSymbol .' ~ '. $price["rate"] .' '. $fiatSymbol .'</span>
                <div class="inputs">
                    <form method="post"> 
                        <table>
                        <tr>
                            <td >
                            <span class="label">Set the withrawal amount:</span><br>
                            <input id="inputBalance" type="number" name="amount" placeholder="'. $balance .'" min="'. $localCurrencyMinimum.'" oninput="setTimeout(()=>{ if(this.value < '. $localCurrencyMinimum .') this.value = '. $localCurrencyMinimum .'; document.getElementById(\'inputFiat\').value = parseFloat(this.value *'. $localCurrencyWithdrawalPrice.').toFixed(2)+\' '. $fiatSymbol.'\';}, 2000);">
                            </td>
                            <td><span style="font-size:40px"> 💱 </span></td>
                            <td >
                            <span class="label">You will recive in '. $cryptoSymbol .':</span><br>
                            <input id="inputFiat" type="text" name="fiat" value="0 '. $fiatSymbol .'" readOnly>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            <span class="label">Set the destination '. $cryptoSymbol .' address:</span><br>
                            <input id="address" type="text" name="address" placeholder="Your '. $cryptoSymbol .' Address..." style="width:490px;font-size:14px;height:30px;" oninput="this.value = this.value.trim();">
                            </td>
                        </tr>
                    
                        <input type="hidden" name="account" value="'. $account .'">
                        <input type="hidden" name="securityHash" value="'. $securityHash .'">
                        <input type="hidden" name="avatarSecureId" value="'. $avatarSecureId .'">
                        <input type="hidden" name="avatarSession" value="'. $avatarSession .'">
                        <tr>
                            <td>
                            <span><b>Important notes:</b></span>
                            <ul style="margin-top: 10px;">
                                <li>The withdrawals are not automatic and may take up to 48h.</li>
                                <li>You will recive the '. $cryptoSymbol .' equivalent (-fees) of the displayed '. $fiatSymbol .' amount at the current rate in real time.</li>
                                <li>The '. $cryptoSymbol .' network fees are at your charge and deduced from the final amount of '. $cryptoSymbol .' that you will recive in your wallet.</li>
                                <li>Don\'t forget to save your Pull Payment ID in the next step. You will need it to get fast support in case of problems.</li>
                            </ul>
                            </td>
                        </tr>
                        <tr>
                            <td style="float:right;">
                            <input class="button btn-green" type="submit" name="withdrawal" value="Request a Withdrawal" style="background-color:darkgreen;margin-top:17px;">
                            </td>
                        </tr>
                        </table>
                    </form>
                </div>';
            }
        }else { 
            echo 
            '<div class="warning-box">
            <span class="warning-img">⚠️</span><br>
            <span class="warning">Security verification failed!</span><br>
            <span class="sub-warning">Please try to logout / login inworld to get a new withdrawal link...<br>Thanks for your patience.</span>
            </div>';

        } 
    }else{
        $uuid = isset($_GET["account"]) ? $_GET["account"] : "";
        echo 
        '<span><b>Step by step guide:</b></span>
        <ul>
            <li>Set your avatar key and click [ Send Me a Withdrawal Link ]. This will open a dialog box in your viewer... click [ Go To Page ].</li>
            <li>In the withdrawal page, set the amount to exchange / withdraw, your '. $cryptoSymbol .' address and click [ Request a Withdrawal ].</li>
            <li>A successful withdrawal request will provide you a BTCPay tracking page where you can get infos about the status of your withdrawal. Also, you will recive inworld notifications about the progress of the blockchain transaction.</li>
        </ul>
        <span><b>Please, set your avatar key:</b></span><br>
        <span class="sub-warning">Your avatar must be / stay online inworld during this operation! </span>
        <div style="text-align: center;margin-bottom: 30px;">
            <form method="post">
                <input id="aviKey" type="text" name="aviKey" value="'. $uuid .'" placeholder="Enter Your Avatar Key" style="width:490px;font-size:18px;height:30px;text-align:center;margin:10px 0;" oninput="this.value = this.value.trim();">
                <br>
                <input class="button btn-red" type="submit" name="get-link" value="Send Me a Withdrawal Link" style="width:auto;margin-top:20px;">
            </form>
        </div>';
    }
}

if(getenv('REQUEST_METHOD') == 'POST') {
    
    if(array_key_exists('aviKey', $_POST) && $_POST['aviKey'] == null){
        echo 
            '<div class="warning-box">
            <span class="warning-img">⚠️</span><br>
            <span class="warning">Missing your avatar UUID key!</span><br>
            <span class="sub-warning">You can get your UUID from your avatar\'s "Profile".<br>Thanks for your patience.</span><br>
            <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
            </div>';
            return;
    }
    else if(array_key_exists('aviKey', $_POST) && isUUID($_POST['aviKey'])){
        $hash = getLinkHash($_POST['aviKey'],$_SERVER['REMOTE_ADDR']);
        $ret = sendWithdrawalLinkRequest($_POST['aviKey'], $hash);
       if($ret){
         echo
            '<div class="warning-box">
            <span class="warning-img">✔️</span><br>
            <span class="success">Security link successfully sent!</span><br>
            <span class="sub-warning">Please open your withdrawal link from your viewer...</span>
            </div>';
            return;
       }else {
        echo 
            '<div class="warning-box">
            <span class="warning-img">⚠️</span><br>
            <span class="warning">Failed to send the withdrawal link!</span><br>
            <span class="sub-warning">Please try to logout / login inworld to get a new withdrawal link...<br>Thanks for your patience.</span><br>
            <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
            </div>';
            return;
       }
    }
    else if(array_key_exists('account', $_POST) 
        && $_POST['account'] == $_GET['a']
        && array_key_exists('securityHash', $_POST)
        && $_POST['securityHash'] == $_GET['sh']){

        $account = $_POST['account'];
        $session = opensim_get_avatar_session($account);
        $avatarSecureId = $session["secureID"];
        $avatarSession = $session["sessionID"];
        $tempHash = getHash($account,$avatarSecureId,$avatarSession,$_SERVER['REMOTE_ADDR']);

        if(hash_equals($tempHash, $_POST['securityHash'])){
            $aviName = opensim_get_avatar_name($account)["fullname"];
            $balance = get_balance($account, $avatarSecureId);

            if(array_key_exists('address', $_POST) && $_POST['address'] != null){
                $destination = $_POST['address'];

            }else{
                echo 
                '<div class="warning-box">
                    <span class="warning-img">⚠️</span><br>
                    <span class="warning">Missing '. $cryptoSymbol .' address!</span><br>
                    <span class="sub-warning">Please set a '. $cryptoSymbol .' address and try again...<br>Thanks for your patience.</span><br>
                    <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
                </div>';
                return;
            }
            if(array_key_exists('amount', $_POST) 
                && $_POST['amount'] >= $localCurrencyMinimum 
                && $_POST['amount'] <= $balance){

                    $price = convert_to_real($_POST['amount'], true);
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
                        echo 
                        '<span>Processing withdrawal verifications for <b>'. $aviName .'</b> ✔️</span><br>
                        <span>Requested amount : <b>'. $_POST['amount'] .' '. $localCurrencySymbol .'</b> ✔️</span><br>
                        <span>You will recive : <b>'. $price['fiatAmount'] .' '. $fiatSymbol .' in '. $cryptoSymbol .'</b> </span><br>
                        <span>Sending Pull Payment request...</span><br>';

                        if ($account != null && $avatarSecureId != null && $_POST['amount'] != null && $destination != null){
                            $ret = requestWithrawal($account, $avatarSecureId, $_POST['amount'], $destination);
                            if($ret['success']){
                                /*echo
                                '<div class="warning-box">
                                <span class="warning-img">✔️</span><br>
                                <span class="success">Withdrawal Successfully Requested!</span><br>
                                <span class="sub-warning">Please open your tracking page to get more infos...<br><b>URL: <a class="" href="'. $ret["link"].'" >'. $ret["link"].'</b></span><br>
                                <input class="button" value="Go To Page" style="margin-top:30px;width:100px;background-color:darkgreen;"></a>
                                </div>';*/
                                header("Location: ".$ret['link']);
                                exit();
                            }else{
                                echo
                                '<div class="warning-box">
                                    <span class="warning-img">⚠️</span><br>
                                    <span class="warning">Withdrawal failed!</span><br>
                                    <span class="sub-warning">'.$ret["errorMessage"].'<br> Please, wait the transaction approuval...<br>Thanks for your patience.</span><br>
                                    <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
                                </div>';
                                return;  
                            }                          
                        }else{
                            echo
                            '<div class="warning-box">
                                <span class="warning-img">⚠️</span><br>
                                <span class="warning">Missing withdrawal parameters!</span><br>
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
                    <span class="sub-warning">The amount in '.$localCurrencySymbol.' must be over the minimum '. $localCurrencyMinimum.' '.$localCurrencySymbol.'<br>and under your balance '.$balance.' '.$localCurrencySymbol.'.</span><br>
                    <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
                </div>';
                return;
            }
        }
        else {
            echo 
            '<div class="warning-box">
                <span class="warning-img">⚠️</span><br>
                <span class="warning">Security verification failed!</span><br>
                <span class="sub-warning">Please try to logout / login inworld to get a new withdrawal link...<br>Thanks for your patience.</span>
                <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
            </div>';
            return;
        }
    }else {
        echo 
            '<div class="warning-box">
                <span class="warning-img">⚠️</span><br>
                <span class="warning">Security verification failed!</span><br>
                <span class="sub-warning">Please try to logout / login inworld to get a new withdrawal link...<br>Thanks for your patience.</span><br>
                <input class="button btn-red" value="Go Back" style="margin-top:30px;width:100px;" onclick="window.history.back();">
            </div>';
            return;
    }
}
?>
       <div id="support"><span>Need help?</span>&nbsp;&nbsp;<a href="#" target="_blank"><span class="support-img">👨‍🔧</span> InWorld</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="#" target="_blank"><span class="support-img">🌐</span> Website</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="#" target="_blank"><span class="support-img">✉️</span> E-Mail</a></div>
        </div> 
    </div>
    

</body>