<?php
// If this file is called directly, abort.
defined('WPINC') || die;

require(__DIR__ .'/bolt11-payment-request-decoder.php');

class LightningAddress
{
    private $lnurl;
    private $address;

    private $client;
    private $decoder;
    private $denormalizer;

    public function __construct()
    {
        $this->decoder = new Bolt11PaymentRequestDecoderWithoutSatoshisAndSignature();
    }

    public function setLnurl($lnurl)
    {
        $this->lnurl = $lnurl;
    }

    public function getInfo()
    {
        $lnurlResponse = $this->request('GET', $this->lnurl);
        $callbackUrl = $lnurlResponse['callback'];
        return ["alias" => $this->address, "identity_pubkey" => "LNURL invoice callback URL: " . $callbackUrl ];
    }

    public function setAddress($address)
    {
        $parts = explode('@', $address);
        $this->address = $address;
        $this->lnurl = 'https://' . $parts[1] . '/.well-known/lnurlp/' . $parts[0];
    }

    public function isConnectionValid()
    {
        return !empty($this->lnurl);
    }

    public function addInvoice($invoice)
    {

        $amountInMilliSats = (int)$invoice['value'] * 1000;
        //$comment = $invoice['memo'];
        $callbackUrl = $this->getCallbackUrl('amount=' . $amountInMilliSats);

        $invoice = $this->request('GET', $callbackUrl);
        $invoice['payment_request'] = $invoice['pr'];

        $pr = $this->decoder->decode($invoice['pr']);
        $tags = $pr['tags'];
        $key = array_search('payment_hash', array_column($tags, 'tag_name'));
        $paymentHash = $tags[$key]['data']; //$pr->findTagByName(\Jorijn\Bitcoin\Bolt11\Model\Tag::PAYMENT_HASH)->getData();

        $invoice['r_hash'] = $paymentHash;

        $verify_url = $invoice['verify'];
        if (!empty($verify_url)) {
            $query = parse_url($verify_url, PHP_URL_QUERY);
            // Returns a string if the URL has parameters or NULL if not
            if ($query) {
                $verify_url .= '&payment_hash=' . $paymentHash;
            } else {
                $verify_url .= '?payment_hash=' . $paymentHash;
            }
        }

        $invoice['id'] = $verify_url; // the id will be passed to the getInvoice function

        return $invoice;
    }

    public function getInvoice($id)
    {
        $status = ['settled' => false];

        if (!empty($id)) {
            try {
                $verify = $this->request('GET', $id);
                $status['settled'] = $verify['settled'];
            } catch(Exception $e) {
                // TODO: log somehow
            }
        }
        return $status;
    }

    public function isInvoicePaid($checkingId)
    {
        return false;
    }

    public function getCallbackUrl($queryParams)
    {
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

    private function request($method, $url, $body = null)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
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

    private function client()
    {
        if ($this->client) {
            return $this->client;
        }
        $this->client = new GuzzleHttp\Client();
        return $this->client;
    }

}

?>
