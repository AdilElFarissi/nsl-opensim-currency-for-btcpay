# Opensimulator Money Server With BTCPay Integration.

### Outline:
This is a modified version of DTL/NSL Secure Money Server by [Fumi.Iseki and NSL](http://www.nsl.tuis.ac.jp) re-worked to interact with [BTCPay Server](https://github.com/btcpayserver/btcpayserver) as cryptocurrencies payment gateway and processor in a self-sovereign context, where you have 100% of the control and you are behind your own system without any fees or Third-parties... Freedom.

[Proof of work video](https://www.youtube.com/watch?v=x7I-mgH-p0c)

### Change Log:
+ Upgraded to dotnet 8 (Cross-platforms, Compatible OpenSim >= 0.9.3).
+ Add support for standalones instances.
+ Add BTCPay related MySQL tables and their respective handlers.
+ Add TLS/SSL support to the region module server.
+ Replaced HttpWebRequest by HttpClient.
+ Add BTCPay related XMLRPC handlers and respective methods in the region module.
+ Add BTCPay related functions in the PHP side to fetch the current rates, request BTCPay invoices and pull payments for withdrawals.
+ Add web interfaces for the currency purchases and withdrawals.
+ Add some security checks and verifications.
+ Removed all unnecessary PHP files for this BTCPay Server integration.
+ Many fixes and optimizations...

### Requirements:
+ A correctly installed and synced BTCPay instance with a correctly set store and wallet. Those can be self-hosted or installed on a VPS... or you can use a third-party host without installations (not recommanded!). more infos here:
https://docs.btcpayserver.org/Deployment/

+ A PHP server under https (important! don't use without TLS/SSL). Due to the removal of the xmlrpc-encode/decode and the json_encode/decode functions in PHP8 without a replacement, the max compatible PHP version is the last PHP7. The PHP part do not work with PHP8.

+ Opensimulator >= v0.9.3 (SSL/TLSed recommanded).

### Viewers Compatibility:
This version is compatible with [Firestorm](http://firestormviewer.org/) Viewer that allows the display of the crypto amounts in the Buy Currency and lands windows... The currency and lands purchases using the viewer windows may not work in the others!
As solution, i added a web interface for the currency purchases and withdrawals to what you can point your end-user. Is also possible to sell/buy back the local currency using LSL scripts (scripts not included!)...
+ For the currency buy:
<pre>https://your-domain-or-ip.here/helper_scripts/helper/currency-buy.php</pre>
+ For the withdrawals:
<pre>https://your-domain-or-ip.here/helper_scripts/helper/withdrawals.php</pre>

### Installation
The installation depend on your operating system and if a compilation is needed but in general is the same for all OS.

0. You need an SSL/TLS valid or self-signed Pkcs12 certificate (.pfx or .p12 extension). Please don't use this software if the server/module/php are not under https! this trilogy exchange some sensive data and is all what a malicious person need to harm! not only in the money level!!!

1. Copy the content of the "ready-bin" folder to "opensim-0.9.3/bin" in linux or "opensim-0.9.3\bin" in windows

2. Upload or copy the "helper_scripts" folder to your PHP7 web server. You can change the folder name if you want... the "helper" folder must be accessible eg;
<pre>https://your-domain-or-ip.here/helper_scripts/helper/</pre>


### Compiling
Compile OpenSim first and place the "btcpay-opensim-currency" in the OpenSimulator source root folder.
+ In Windows, run or double click
<pre>opensim-0.9.3-source\btcpay-opensim-currency\runprebuild.bat</pre>
This will create the solution and produce a compile.bat files... run or double click
<pre>opensim-0.9.3-source\btcpay-opensim-currency\compile.bat</pre>
A successful compilation will add a "bin" folder inside "btcpay-opensim-currency" with the target sub-folder "net8.0" where you will find the Money Server executable and the module DLLs...
<pre>opensim-0.9.3-source\btcpay-opensim-currency\bin\net8.0\</pre>

+ In Linux&Co, is the same as Windows but the file extension is .sh
<pre>
  cd opensim-0.9.3-source
  cd btcpay-opensim-currency
  ./runprebuild.sh
  ./compile.sh
</pre>

### Settings
1. Money Server

Open "opensim-0.9.3/bin/MoneyServer.ini" or "opensim-0.9.3\bin\MoneyServer.ini" with a text editor or IDE.

  - Set the OpenSim MySQL hostname, database, username and password in the [MySql] section (must be the same as [DatabaseService] in your StandaloneCommun or GridCommun .ini).
  
  - If you use a Banker Avatar, please set UUID of Banker Avatar to "BankerAvatar" in MoneyServer.ini. When 00000000-0000-0000-0000-000000000000 is specified as UUID, all avatars can get money from system.
  
  - If you want to normally use llGiveMoney() function even when payer doesn't login to OpenSim, you must set "true" to "enableForceTransfer".
  
  - If you want to send money to another avatar by PHP script, you must set "true" to "enableScriptSendMoney" And please set "MoneyScriptAccessKey" and "MoneyScriptIPaddress". "MoneyScriptAccessKey" is Secret key of Helper Script. Specify same key in include/config.php and OpenSim.ini. "MoneyScriptIPaddress" is IP address of server that Helper Script execute at. Not specify 127.0.0.1. 
  
  - If you want to change Update Balance Messages (blue dialog), pleaase enable and rewrite "BalanceMessage..." valiables.

2. Region Server

Open "opensim-0.9.3/bin/OpenSim.ini" or "opensim-0.9.3\bin\OpenSim.ini" with a text editor or IDE and scroll down to the  [Economy] section. Open "config\OpenSim.ini.sample" copy/paste and modify what is needed. eg:
<pre>
 [Economy]
  ; Set the economy module name.
    EconomyModule = DTLNSLMoneyModule

  ; Do you want to allow 0 currency transactions?
    SellEnabled = true

  ; Set your PHP web server URL pointing to the "helper" folder with "/" at the end.
    Economy = "https://your-domain-or-ip.here/helper_scripts/helper/

  ; Set the Money Server URL.
    CurrencyServer = "https://your-domain-or-ip.here:8008" 

  ; Verify the server certificate? keep false... (buggy! may not work)
    CheckServerCert = false

  ; Set the module certificate and password.
    ClientCertFilename = "SineWaveCert.pfx"
    ClientCertPassword = "123"

  ; Set the things prices...
    PriceUpload = 10
    PriceGroupCreate = 100
  
  ; Mesh upload factors
    MeshModelUploadCostFactor = 2.5
    MeshModelUploadTextureCostFactor = 1.0
    MeshModelMinCostFactor = 2.0

  ; Set the minimum amount to buy currency from the system.
    MinimumAmount = 250

  ; Set the same secret key as MoneyScriptAccessKey in include/config.php and MoneyServer.ini
    MoneyAccessKey  = "123456789"
  
  ; Avatar Class for HG Avatar
  ; {ForeignAvatar, HGAvatar, GuestAvatar, LocalAvatar} HGAvatar
  ; HG Avatar is assumed as a specified avatar class. Default is HGAvatar
  ; Processing for each avatar class is dependent on Money Server settings.
    HGAvatarAs = "HGAvatar"
</pre>
 Notes: Do not use 127.0.0.1 or localhost for CurrencyServer's address. This address is used for identification of user on Money Server.

3. PHP Helper Scripts

Open "/helper_scripts/include/config.php", read the guides "comments" and follow the instructions to get/set what is needed for the BTCPay integration.

Note: Please do your best to protect the "include" folder and the config.php file with  strict permissions and htaccess if supported.

### Execution
1. Standalones:
+ In windows, run or double click MoneyServer.exe before OpenSim.exe.
+ In linux&Co:
<pre>
  # cd opensim-0.9.3
  # cd bin
  # dotnet MoneyServer.dll
  # dotnet OpenSim.dll
</pre>
2. Grids
+ In windows, run or double click Robust.exe, next MoneyServer.exe before OpenSim.exe.
+ In linux&Co:
<pre>
 # cd opensim-0.9.3
 # cd bin
 # dotnet Robust.dll
 # dotnet MoneyServer.dll
 # dotnet OpenSim.dll
</pre>
When done and all started without problems, use Firestorm to test a currency purchase from the viewer. You can also use the web interfaces to test.
+ For the currency buy:
<pre>https://your-domain-or-ip.here/helper_scripts/helper/currency-buy.php</pre>
+ For the withdrawals:
<pre>https://your-domain-or-ip.here/helper_scripts/helper/withdrawals.php</pre>


### Web Interfaces.
The currency purchases web interface can take 2 parameters that auto-fill the form:
+ account: Avatar UUID.
+ amount: The amount of local currency to buy.
  
So, you can point the user to this page using llLoadURL...
<pre>https://your-domain-or-ip.here/helper_scripts/helper/currency-buy.php?account=UUID&amount=1234</pre>

The withdrawal web interface can take 1 parameter:
+ account : Avatar UUID.
<pre>https://your-domain-or-ip.here/helper_scripts/helper/withdrawals.php?account=UUID</pre>


### Attention.
This is unofficial software. Please do not inquire to OpenSim development team or DTL Currency Processing development team or NSL team about this. 

### Exemption from responsibility.
THIS SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Use at your own risks!

### Thanks where due.
 + This is a modified version of DTL/NSL Secure Money Server by [Fumi.Iseki and NSL](http://www.nsl.tuis.ac.jp)
 + Special thanks to [Fumi.Iseki and NSL Team](http://www.nsl.tuis.ac.jp).
 + Special thanks to BTCPay Server Team.
 + Special thanks to [OpenSimulator Team](http://opensimulator.org/).

Thank you very much!!!

