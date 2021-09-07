<?php

require_once 'vendor/autoload.php';

class LightningAddress {
  private $lnurl;
  private $address;

  private $client;
  private $decoder;
  private $denormalizer;

  public function __construct() {
    $this->decoder = new \Jorijn\Bitcoin\Bolt11\Encoder\PaymentRequestDecoder();
    $this->denormalizer = new \Jorijn\Bitcoin\Bolt11\Normalizer\PaymentRequestDenormalizer();
  }

  public function setLnurl($lnurl) {
    $this->lnurl = $lnurl;
  }

  public function getInfo() {
    return ["alias" => $this->address, "identity_pubkey" => "LNURL: " . $this->lnurl];
  }

  public function setAddress($address) {
    $parts = explode('@', $address);
    $this->address = $address;
    $this->lnurl = 'https://' . $parts[1] . '/.well-known/lnurlp/' . $parts[0];
  }

  public function isConnectionValid() {
    return !empty($this->lnurl);
  }

  public function addInvoice($invoice) {

    $amountInMilliSats = (int)$invoice['value'] * 1000;
    //$comment = $invoice['memo'];
    $callbackUrl = $this->getCallbackUrl('amount=' . $amountInMilliSats);

    $invoice = $this->request('GET', $callbackUrl);
    $invoice['payment_request'] = $invoice['pr'];

    $pr = $this->denormalizer->denormalize($this->decoder->decode($invoice['pr']));
    $paymentHash = $pr->findTagByName(\Jorijn\Bitcoin\Bolt11\Model\Tag::PAYMENT_HASH)->getData();

    $invoice['r_hash'] = $paymentHash;

    return $invoice;
  }

  public function getInvoice($checkingId) {
    return ['settled' => false];
    }

    public function isInvoicePaid($checkingId) {
      return false;
    }

  public function getCallbackUrl($queryParams) {
    $lnurl = $this->request('GET', $this->lnurl);
    $callbackUrl = $lnurl['callback'];

    // append the query params - check if the callbackUrl has already a query param
    $query = parse_url($callbackUrl, PHP_URL_QUERY);
    if ($query) {
      $callbackUrl .= '&' . $queryParams;
    } else {
      $callbackUrl .= '?' . $queryParams;
    }
    return $callbackUrl;
  }

  private function request($method, $url, $body = null) {
    $headers = [
      'Content-Type' => 'application/json'
    ];

    $request = new GuzzleHttp\Psr7\Request($method, $url, $headers, $body);
    $response = $this->client()->send($request);
    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
      $responseBody = $response->getBody()->getContents();
      return json_decode($responseBody, true);
    } else {
      // raise exception
    }
  }

  private function client() {
    if ($this->client) {
      return $this->client;
    }
    $this->client = new GuzzleHttp\Client();
    return $this->client;
  }

}

?>