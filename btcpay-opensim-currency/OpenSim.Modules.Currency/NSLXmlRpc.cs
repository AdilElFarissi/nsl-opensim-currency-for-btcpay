/* 
 * Copyright (c) Contributors, http://www.nsl.tuis.ac.jp
 * Modified by Adil El Farissi for BTCPay Server Integration 11-2023.
 */


using System;
using System.Collections;
using System.Reflection;
using System.Security.Cryptography.X509Certificates;

using log4net;
using Nwc.XmlRpc;
using System.Net.Http;

namespace NSL.Network.XmlRpc
{
    public class NSLXmlRpcRequest : XmlRpcRequest
	{
		private static readonly ILog m_log = LogManager.GetLogger(MethodBase.GetCurrentMethod().DeclaringType);

		public NSLXmlRpcRequest(){}

		public Hashtable SendXMLRPCRequest(Hashtable reqParams, string method, string url, X509Certificate2 myClientCert, bool checkServerCert, int timeout)
        {
            m_log.Debug($"[MONEY XML-RPC]: Sending request '{method}' to {url}");
            
			HttpClient client = null;
			HttpClientHandler ch = new();
			if( myClientCert != null) ch.ClientCertificates.Add(myClientCert);
			if(!checkServerCert) ch.ServerCertificateCustomValidationCallback += (sender, myClientCert, chain, sslPolicyErrors) => true;

            client = new HttpClient(ch, false)
            {
                Timeout = TimeSpan.FromMilliseconds(timeout)
            };
            client.DefaultRequestHeaders.Add("UserAgent", "NSLXmlRpcRequest");
			if (!checkServerCert) client.DefaultRequestHeaders.Add("NoVerifyCert", "true");

            try
            {
                ArrayList sendParams = new(){ reqParams };
                XmlRpcRequest Req = new(method, sendParams);
                XmlRpcResponse Resp = Req.Send(url, client);

                if (Resp.IsFault)
                {
                    m_log.Debug($"[MONEY XML-RPC]: XML-RPC request '{method}' to {url} FAILED: FaultCode={Resp.FaultCode}, {Resp.FaultString}");
					client?.Dispose();
                    return null;
                }

                Hashtable RespData = (Hashtable)Resp.Value;
                return RespData;
            }
			catch (Exception e)
			{
				m_log.Debug($"[MONEY XML-RPC]: XML-RPC Exception '{e.Message}'");
				client?.Dispose();
				return null;
			}
            finally
            {
                client?.Dispose();
            }
        }
	}
}
