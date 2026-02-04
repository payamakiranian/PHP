<?php

namespace PayamakIranian;

use SoapClient;

class Client
{
    private string $username;
    private string $password;
    private string $sender;

    // URLهای REST و SOAP اصلی
    private string $restUrl = "https://api.payamak-iranian.com/sms/send";
    private string $restBulkUrl = "https://api.payamak-iranian.com/sms/sendBulk";
    private string $restBalanceUrl = "https://api.payamak-iranian.com/sms/balance";
    private string $restStatusUrl = "https://api.payamak-iranian.com/sms/status";
    private string $soapWsdl = "https://api.payamak-iranian.com/soap?wsdl";

    private ?SoapClient $soapClient = null;

    public function __construct(string $username, string $password, string $sender)
    {
        $this->username = $username;
        $this->password = $password;
        $this->sender = $sender;
    }

    /** -------------------- REST Methods -------------------- */

    public function sendSMS(string $to, string $message): bool
    {
        return $this->postRequest($this->restUrl, [
            'to' => $to,
            'text' => $message
        ]);
    }

    public function sendBulkSMS(array $recipients, string $message): bool
    {
        $to = implode(',', $recipients);
        return $this->postRequest($this->restBulkUrl, [
            'to' => $to,
            'text' => $message
        ]);
    }

    public function sendScheduledSMS(string $to, string $message, string $sendDate): bool
    {
        return $this->postRequest($this->restUrl, [
            'to' => $to,
            'text' => $message,
            'sendDate' => $sendDate // YYYY-MM-DD HH:MM:SS
        ]);
    }

    public function getBalance(): float
    {
        $response = $this->postRequest($this->restBalanceUrl);
        return floatval($response);
    }

    public function getSMSStatus(string $messageId): string
    {
        return $this->postRequest($this->restStatusUrl, [
            'messageId' => $messageId
        ]);
    }

    /** -------------------- SOAP Methods -------------------- */

    private function initSoap(): void
    {
        if ($this->soapClient === null) {
            $this->soapClient = new SoapClient($this->soapWsdl);
        }
    }

    public function sendSMSSoap(string $to, string $message): bool
    {
        $this->initSoap();
        $result = $this->soapClient->SendSMS([
            'username' => $this->username,
            'password' => $this->password,
            'from' => $this->sender,
            'to' => $to,
            'text' => $message
        ]);
        return $result->status === 'OK';
    }

    public function sendBulkSMSSoap(array $recipients, string $message): bool
    {
        $this->initSoap();
        $to = implode(',', $recipients);
        $result = $this->soapClient->SendSMS([
            'username' => $this->username,
            'password' => $this->password,
            'from' => $this->sender,
            'to' => $to,
            'text' => $message
        ]);
        return $result->status === 'OK';
    }

    /** -------------------- Internal helper -------------------- */
    private function postRequest(string $url, array $data = []): mixed
    {
        $data = array_merge([
            'username' => $this->username,
            'password' => $this->password,
            'from' => $this->sender
        ], $data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        if ($response === false) {
            throw new SMSException(curl_error($ch));
        }
        curl_close($ch);

        return $response;
    }
}
