<?php
//
// Configration file for non Web Interface
// v0.9.0Dev  2016100500
// Modified by Adil El Farissi for the BTCPay integration.
// v0.9.3Dev  2023112700

// Please set this hepler script directory Url
if (!defined("ENV_HELPER_URL")) define("ENV_HELPER_URL",  "https://your-domain-or-ip.here/helper_scripts/helper/");
// Please set this hepler script directory absolute path.
if (!defined("ENV_HELPER_PATH")) define("ENV_HELPER_PATH", "D:\\xampp\\htdocs\\helper_scripts\\helper\\" );


/** Database Settings**/
/* Please set OpenSim MySQL DB access informations. Must be the same as [DatabaseService]  => "ConnectionString" in StandaloneCommon.ini for standalones or Robust.ini for grids.*/
define("OPENSIM_DB_HOST","localhost");
define("OPENSIM_DB_NAME","opensim");
define("OPENSIM_DB_USER","db_user");
define("OPENSIM_DB_PASS","db_password");
define("OPENSIM_DB_MYSQLI",1);

/** BTCPay settings **/
/* Set your BTCPay URL, if you are not using the standard ports (80 or 443), you have to add it to your URL eg; if your port is 7777 set https://example.com:7777 */
define("BTCPAY_SERVER_URL", "https://testnet.demo.btcpayserver.org");

/* Set your store ID. You can get this after creating a store in your BTCPay instance under "Settings" in the left menu > General */
define("BTCPAY_STORE_ID", "FzeG58ybj2hU9XEkNEz8NqPrJq8LAtYXvZ5MEqfg6gT6");

/* Set your BTCPay account API Key ( Sensive Data !):
The API Key is used to request the invoices and pull payments from your BTCPay stores via the BTCPay's Greenfield API and must be created and set with only 3 permissions "Create an Invoice", "Create non-approved pull payments" and "Archive your pull payments". You can generate an API Key under "Account" in the left menu > "Manage Account" > API Keys. more infos here :
https://docs.btcpayserver.org/API/Greenfield/v1/#section/Authentication */
define("BTCPAY_API_KEY", "5ff92cd7338711627a075f05dd615f98ac8aefbd");

/* The Webhook system in BTCPay send the invoices notifications to the configured destination. in our case the destination is the webhook.php file located in "helper_scripts/helper/webhook.php" that handles the currency delivery to the customer after a seccessful payment / confirmation and the withdrawals.
The following values are used to check the origin and the HMAC of the incoming notifications and are sensive data.
To get your Webhook ID and Secret password, click your store "Settings" in the left menu > Webhooks, create a new webhook and set the full URL of the webhook.php file eg;
https://your-domain-or-ip.here/helper_scripts/helper/webhook.php
When done click "Modify" and copy your webhook ID from the URL in the browser address bar, the ID is the last value in the URL (after the last /) eg;
https://YourBTCPayDomainName.here/stores/FzeG58ybj2hU9XEkNEz8NqPrJq8LAtYXvZ5MEqfg6gT6/webhooks/HwR1T6LKgJkZkbNUvKj41N

in this example your Webhook ID is : HwR1T6LKgJkZkbNUvKj41N
You can get your Webhook Secret pass in the same page "Modify". more infos here :
https://docs.btcpayserver.org/API/Greenfield/v1/#tag/Webhooks */
define("BTCPAY_WEBHOOK_ID", "HwR1T6LKgJkZkbNUvKj41N");
define("BTCPAY_WEBHOOK_SECRET", "x7B3mpvqiyZpCkgD7q3EdmWM5Ke");

/* BTCPay get the rates and calculate in real time the equivalent of the your fiat prices in crypto based the following settings:
- BTCPAY_FIAT : The fiat ticker or symbol in uppercase eg; USD EUR GBP CAD... (Fiat only here!). This value depends on the availabilty of the fiat currency in the rates data provider that you can get and set in your store "Settings" > Rates.
- BTCPAY_PAYMENT_CRYPTOCURRENCY : The cryptocurrency ticker or symbol in uppercase that you want to recive as payment eg; LTC, DOGE, XMR, BTC... (Cryptos only here!)
NOTE: Not all the cryptocurrencies prices are adapted to the micro-payments and BTCPay supports only few ones... so, we are limited to the this list:
https://docs.btcpayserver.org/FAQ/Altcoin/

Recommanded: 
- Dogecoin (DOGE): adapted value/price for the hypergrid economy and available in the majority of exchanges... DOGE do some surprises time to time like x2 (or /2!) in the rates.
- Monero (XMR): For privacy and a low ecological impact comparing with many others like Bitcoin. Also Monero is a CPU only minable coin, so you can setup a Monero miner aside your opensim and/or BTCPay instance and mine Monero when there is nobody inWorld...*/
define("BTCPAY_FIAT", "USD");
define("BTCPAY_PAYMENT_CRYPTOCURRENCY", "BTC");

/** End BTCPay settings **/

/**  Local currency settings **/
/* Set your bankster avatar UUID to recive the local currency of the withdrawals. This must be the same as BankerAvatar in MoneyServer.ini*/
define("LOCAL_CURRENCY_BANKSTER", "6217ab96-5e6b-4815-9f2f-c2962262adc8");

/* Set the price of 1 local currency unit in 'BTCPAY_FIAT'. eg; if 'BTCPAY_FIAT' is EUR  and the 'LOCAL_CURRENCY_PRICE' is set to 0.01 this mean 1 Euro cent and 1€ = 100 local currency units.
Default: 0.004 or 1 'BTCPAY_FIAT' = 250 local currency. */
define("LOCAL_CURRENCY_PRICE", 0.004);

/* The LOCAL_CURRENCY_WITHDRAWAL_PRICE is the local currency buyback price for withdrawals.
If LOCAL_CURRENCY_PRICE > LOCAL_CURRENCY_WITHDRAWAL_PRICE you do profit...
If LOCAL_CURRENCY_PRICE = LOCAL_CURRENCY_WITHDRAWAL_PRICE no profit...
If LOCAL_CURRENCY_PRICE < LOCAL_CURRENCY_WITHDRAWAL_PRICE you lose money...
Default: 0.0036 or 250 local currency = 0.9 'BTCPAY_FIAT' (10% profit is a max!). */
define("LOCAL_CURRENCY_WITHDRAWAL_PRICE", 0.0036);

/* Set the minimum amount to buy and withdraw. This is useful to keep your currency profitable or if you want to limit the currency buy to a category of users like the currencies resellers... In this case you have to adapt your LOCAL_CURRENCY_PRICE to let a profit margin to those entities eg; 0.0038 for a LOCAL_CURRENCY_MIN_AMOUNT >= 50k units.
Default: 250. for a public price / limit based on defaults...*/
define("LOCAL_CURRENCY_MIN_AMOUNT", 250);

/* Set the local currency symbol. 
- For a standalone, this must be the same as [LoginService] => "Currency" in your StandaloneCommon.ini.
- For a grid, this must be the same as [LoginService] => "Currency" in your Robust.ini */
define("LOCAL_CURRENCY_SYMBOL", "B$");

/** Security settings **/
/* Disable the SSL/TLS certificates verifications for self-signed or invalid certificates.
Default: 0 for valid certs. If set to 1, will skip the cURL certs verification.*/
define("DISABLE_SSL_VERIFICATION", 1);

/* Disable currency purchases. set to 1 to disable */
define("DISABLE_CURRENCY_PURCHASE", 0);

/* Disable currency withdrawals. set to 1 to disable */
define("DISABLE_CURRENCY_WITHDRAWAL", 0);

/* Needed by the BTCPay integration to interact with the DTL/NSL Region Money Module. 
Default: 1. If set to 0 the helper scripts will not send requests or data to Region Module */
define("USE_CURRENCY_SERVER", 1);

/* DTL/NSL Money Server Access Key:
Please set a strong password here!. Must be same key as MoneyScriptAccessKey in MoneyServer.ini and MoneyAccessKey in OpenSim.ini */
define("CURRENCY_SCRIPT_KEY", "123456789");

/** End Security settings **/

/** System settings **/
define("USE_UTC_TIME",  0);
define("DATE_FORMAT",  "d.m.Y - H:i");
define("SYSURL", ENV_HELPER_URL);
$GLOBALS["xmlrpc_internalencoding"] = "UTF-8";
if (USE_UTC_TIME) date_default_timezone_set("UTC");
if (!defined("ENV_READ_CONFIG")) define("ENV_READ_CONFIG", "YES");

/** End System settings **/