/*
 * Copyright (c) Contributors, http://opensimulator.org/, http://www.nsl.tuis.ac.jp/
 * See CONTRIBUTORS.TXT for a full list of copyright holders.
 * Modified by Adil El Farissi for BTCPay Server Integration 11-2023.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the OpenSim Project nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE DEVELOPERS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

//using System.Linq;
using OpenMetaverse;
using OpenSim.Data.MySQL.MySQLMoneyDataWrapper;

namespace OpenSim.Grid.MoneyServer
{
    public interface IMoneyDBService
    {
        int GetBalance(string userID);
        bool WithdrawMoney(UUID transactionID, string senderID, int amount);
        bool GiveMoney(UUID transactionID, string receiverID, int amount);
        bool AddTransaction(TransactionData transaction);
        bool AddUser(string userID, int balance, int status, int type);
        bool UpdateTransactionStatus(UUID transactionID, int status, string description);
        bool SetTransExpired(int deadTime);
        bool ValidateTransfer(string secureCode, UUID transactionID);
        TransactionData FetchTransaction(UUID transactionID);
        TransactionData FetchTransaction(string userID, int startTime, int endTime, int lastIndex);
        int GetTransactionNum(string userID, int startTime, int endTime);
        bool DoTransfer(UUID transactionUUID);
        bool DoAddMoney(UUID transactionUUID);		// Added by Fumi.Iseki
        bool TryAddUserInfo(UserInfo user, UserPresence data);
        UserInfo FetchUserInfo(string userID);
        UserPresence FetchUserPresence(string userID);
        bool RemoveUserPresence(string userID);
    }
}
