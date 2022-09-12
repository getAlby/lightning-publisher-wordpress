<?php

declare(strict_types=1);

// If this file is called directly, abort.
defined('WPINC') || die;


/* This is the payment request decoder without parsing amount values. parsing amount values requires some bc-match library which seems not always available in the default PHP installations.
 * We do not need number because we only want to get the payment hash
 *
 * original file: https://raw.githubusercontent.com/Jorijn/bitcoin-bolt11/master/src/Encoder/PaymentRequestDecoder.php
*/


/*
 * This file is part of the PHP Bitcoin BOLT11 package.
 *
 * (c) Jorijn Schrijvershof <jorijn@jorijn.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// namespace Jorijn\Bitcoin\Bolt11\Encoder;

use function BitWasp\Bech32\decodeRaw;
use function BitWasp\Bech32\encode;

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Bitcoin;
//use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
//use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Signature\CompactSignatureSerializer;
//use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Network\Networks\Bitcoin as BitcoinMainnet;
use BitWasp\Bitcoin\Network\Networks\BitcoinTestnet;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;
use Jorijn\Bitcoin\Bolt11\Exception\InvalidAmountException;
use Jorijn\Bitcoin\Bolt11\Exception\InvalidPaymentRequestException;
use Jorijn\Bitcoin\Bolt11\Exception\SignatureDoesNotMatchPayeePubkeyDecodeException;
use Jorijn\Bitcoin\Bolt11\Exception\SignatureIncorrectOrMissingException;
use Jorijn\Bitcoin\Bolt11\Exception\UnableToDecodeBech32Exception;
use Jorijn\Bitcoin\Bolt11\Exception\UnknownFallbackAddressVersionException;
use Jorijn\Bitcoin\Bolt11\Exception\UnknownNetworkVersionException;
use Jorijn\Bitcoin\Bolt11\Exception\UnrecoverableSignatureException;

/**
 * This class decodes BOLT11 payment requests into plain PHP array data.
 *
 * @see https://github.com/lightningnetwork/lnd/blob/master/zpay32/decode.go
 * @see https://github.com/bitcoinjs/bolt11/blob/7251d6d14630f7d8adc3fcc92a3bbfe9000b6e4e/payreq.js
 * @see https://lightningdecoder.com/
 * @see https://github.com/nievk/bolt11/blob/main/bolt11/__init__.py
 *
 * This class is a PHP port from pre-existing BOLT11 libraries.
 */
class Bolt11PaymentRequestDecoderWithoutSatoshisAndSignature
{
    public const DIVISORS = [
        'm' => '1000.0000000000',
        'u' => '1000000.0000000000',
        'n' => '1000000000.0000000000',
        'p' => '1000000000000.0000000000',
    ];

    public const MAX_MILLISATS = '2100000000000000000.0000000000';
    public const MILLISATS_PER_BTC = '100000000000.0000000000';
    public const NAME_UNKNOWN_TAG = 'unknownTag';

    public const TAG_CODES = [
        'payment_hash' => 1, // p
        'description' => 13, // d
        'payee_node_key' => 19, // n
        'purpose_commit_hash' => 23,  // h
        'expire_time' => 6, // x
        'min_final_cltv_expiry' => 24, // c
        'fallback_address' => 9, // f
        'routing_info' => 3, // r
        'secret' => 16,
    ];

    /** @var string[] */
    protected $tagNames = [];

    /** @var \Closure[] */
    protected $tagParsers = [];

    /** @var EcAdapterInterface */
    protected $ecAdapter;

    public function __construct($ecAdapter = null)
    {
        $this->ecAdapter =  null; //$ecAdapter ?? Bitcoin::getEcAdapter();
        $this->tagNames = array_flip(self::TAG_CODES);
        $this->initializeTagParsers();
    }

    public function decode(string $paymentRequest): array
    {
        try {
            [$prefix, $words] = decodeRaw($paymentRequest);
        } catch (\Throwable $exception) {
            throw new UnableToDecodeBech32Exception($exception->getMessage(), $exception->getCode(), $exception);
        }

        if (0 !== strpos($prefix, 'ln')) {
            throw new InvalidPaymentRequestException('data does not appear to be a bolt11 invoice');
        }

        // signature is always 104 words on the end
        // cutting off at the beginning helps since there's no way to tell
        // ahead of time how many tags there are.
        $signatureWords = \array_slice($words, -104);

        // grabbing a copy of the words for later, words will be sliced as we parse.
        $wordsWithoutSignature = \array_slice($words, 0, -104);
        $words = \array_slice($words, 0, -104);
        $signatureBuffer = $this->wordsToBuffer($signatureWords);
        $recoveryID = (int) $signatureBuffer->slice(-1)->getInt();

        // get remaining signature buffer after extracting recovery flag
        $signatureBuffer = $signatureBuffer->slice(0, -1);
        if (!\in_array($recoveryID, [0, 1, 2, 3], true) || 64 !== $signatureBuffer->getSize()) {
            throw new SignatureIncorrectOrMissingException('signature is missing or incorrect');
        }

        // without reverse lookups, can't say that the multiplier at the end must
        // have a number before it, so instead we parse, and if the second group
        // doesn't have anything, there's a good chance the last letter of the
        // coin type got captured by the third group, so just re-regex without
        // the number.
        preg_match('/^ln(\S+?)(\d*)([a-zA-Z]?)$/', $prefix, $prefixMatches);

        if ($prefixMatches && !$prefixMatches[2]) {
            preg_match('/^ln(\S+)$/', $prefix, $prefixMatches);
        }

        if (!$prefixMatches) {
            throw new InvalidPaymentRequestException('not a proper lightning payment request');
        }

        $bech32Prefix = $prefixMatches[1];
        $coinNetwork = $this->getNetworkFromPrefix($bech32Prefix);

        // parse the value from hrp
        $value = $prefixMatches[2] ?? null;
        $satoshis = null;
        $milliSatoshis = null;
        /*
        // removed because of errors when bc-math is not available
        if ($value) {
            $divisor = $prefixMatches[3];

            try {
                $satoshis = (int) $this->hrpToSat($value.$divisor);
            } catch (\Throwable $exception) {
                $satoshis = 0;
                $removeSatoshis = true;
            }

            $milliSatoshis = (int) $this->hrpToMillisat($value.$divisor);
        } else {
            $satoshis = null;
            $milliSatoshis = null;
        }
        */

        // reminder: left padded 0 bits, parse the timestamp
        $timestamp = $this->wordsToIntBE(\array_slice($words, 0, 7));
        $timestampString = date(\DateTime::ATOM, $timestamp);
        $words = \array_slice($words, 7); // trim off the left 7 words

        // parse the tags from the available words
        $tags = $this->parseTagsFromWords($words, $coinNetwork);

        $timeExpireDate = $timeExpireDateString = null;
        if ($this->tagsContainItem($tags, $this->tagNames[6])) {
            $timeExpireDate = $timestamp + $this->tagsItems($tags, $this->tagNames[6]);
            $timeExpireDateString = date(\DateTime::ATOM, $timeExpireDate);
        }

        $toSign = Buffertools::concat(
            new Buffer($prefix),
            $this->wordsToBuffer($wordsWithoutSignature, false),
        );

        $payReqHash = Hash::sha256($toSign);
        // $sigPubkey = $this->extractVerifyPublicKey($recoveryID, $signatureBuffer, $payReqHash, $tags);

        $finalResult = [
            'prefix' => $prefix,
            'network' => $coinNetwork,
            'satoshis' => $satoshis,
            'milli_satoshis' => $milliSatoshis,
            'timestamp' => $timestamp,
            'timestamp_string' => $timestampString,
            'payee_node_key' => null, //$sigPubkey->getHex(),
            'signature' => null, //$signatureBuffer->getHex(),
            'recovery_flag' => $recoveryID,
            'tags' => $tags,
            '_payment_request_hash' => $payReqHash->getHex(),
            '_message_to_sign' => $toSign->getHex(),
        ];

        if ($removeSatoshis ?? false) {
            unset($finalResult['satoshis']);
        }

        if (null !== $timeExpireDate) {
            $finalResult['expiry_timestamp'] = $timeExpireDate;
            $finalResult['expiry_datetime'] = $timeExpireDateString;
        }

        return $finalResult;
    }

    public function convert(array $data, $inBits, $outBits): array
    {
        $value = $bits = 0;
        $maxV = (1 << $outBits) - 1;

        $result = [];
        foreach ($data as $item) {
            $value = ($value << $inBits) | $item;
            $bits += $inBits;

            while ($bits >= $outBits) {
                $bits -= $outBits;
                $result[] = ($value >> $bits) & $maxV;
            }
        }

        if ($bits > 0) {
            $result[] = ($value << ($outBits - $bits)) & $maxV;
        }

        return $result;
    }

    public function hrpToSat(string $hrpString): string
    {
        $milliSatoshis = $this->hrpToMillisat($hrpString);

        if ('0.0000000000' === bcmod($milliSatoshis, self::DIVISORS['m'])) {
            throw new InvalidAmountException('amount is outside of valid range');
        }

        return bcdiv($milliSatoshis, self::DIVISORS['m']);
    }

    public function hrpToMillisat(string $hrpString): string
    {
        $divisor = null;
        if (preg_match('/^[munp]$/', substr($hrpString, -1))) {
            $divisor = substr($hrpString, -1);
            $value = substr($hrpString, 0, -1);
        } elseif (preg_match('/^[^munp0-9]$/', substr($hrpString, -1))) {
            throw new InvalidAmountException('not a valid multiplier for the amount');
        } else {
            $value = $hrpString;
        }

        if (!preg_match('/^\d+$/', $value)) {
            throw new InvalidAmountException('not a valid human readable amount');
        }

        $valueBN = bcmul($value, '1', 10);
        $milliSatoshisBN = $divisor
            ? bcdiv(bcmul($valueBN, self::MILLISATS_PER_BTC), self::DIVISORS[$divisor])
            : bcmul($valueBN, self::MILLISATS_PER_BTC);

        if (('p' === $divisor && '0' !== bcmod($valueBN, '10.0000000000'))
            || 1 === bccomp($milliSatoshisBN, self::MAX_MILLISATS)) {
            throw new InvalidAmountException('amount is outside valid range');
        }

        return $milliSatoshisBN;
    }

    protected function initializeTagParsers(): void
    {
        $this->tagParsers[1] = [$this, 'wordsToHex'];
        $this->tagParsers[16] = [$this, 'wordsToHex'];
        $this->tagParsers[13] = [$this, 'wordsToUtf8'];
        $this->tagParsers[19] = [$this, 'wordsToHex'];
        $this->tagParsers[23] = [$this, 'wordsToHex'];
        $this->tagParsers[6] = [$this, 'wordsToIntBE']; // default: 3600 (1 hour)
        $this->tagParsers[24] = [$this, 'wordsToIntBE']; // default: 9
        $this->tagParsers[9] = [$this, 'fallbackAddressParser'];
        $this->tagParsers[3] = [$this, 'routingInfoParser']; // for extra routing info (private etc.)
    }

    protected function wordsToBuffer(array $words, bool $trim = true): BufferInterface
    {
        $buffer = Buffer::hex(bin2hex(implode('', array_map('chr', $this->convert($words, 5, 8)))));

        if ($trim && 0 !== \count($words) * 5 % 8) {
            $buffer = $buffer->slice(0, -1);
        }

        return $buffer;
    }

    protected function getNetworkFromPrefix(string $bech32Prefix): NetworkInterface
    {
        switch ($bech32Prefix) {
            case 'bc':
                return new BitcoinMainnet();

            case 'tb':
                return new BitcoinTestnet();

            default:
                throw new UnknownNetworkVersionException('unknown network for invoice');
        }
    }

    protected function wordsToIntBE(array $words): int
    {
        $total = 0;
        foreach (array_reverse($words) as $index => $item) {
            $total += $item * (32 ** $index);
        }

        return $total;
    }

    protected function parseTagsFromWords(array $words, NetworkInterface $coinNetwork): array
    {
        $tags = [];
        while (!empty($words)) {
            try {
                $tagCode = (string) $words[0];
                $tagName = $this->tagNames[$tagCode] ?? self::NAME_UNKNOWN_TAG;
                $parser = $this->tagParsers[$tagCode] ?? $this->getUnknownParser($tagCode);
                $words = \array_slice($words, 1);

                $tagLength = $this->wordsToIntBE(\array_slice($words, 0, 2));
                $words = \array_slice($words, 2);

                $tagWords = \array_slice($words, 0, $tagLength);
                $words = \array_slice($words, $tagLength);

                if (52 !== $tagLength && \in_array((int) $tagCode, [1, 23, 16], true)) {
                    // MUST skip p, h, s fields that do NOT have data_lengths of 52.
                    continue;
                }

                if (53 !== $tagLength && 19 === (int) $tagCode) {
                    // MUST skip n fields that do NOT have data_length 53.
                    continue;
                }

                $tags[] = ['tag_name' => $tagName, 'data' => $parser($tagWords, $coinNetwork)];
            } catch (UnknownFallbackAddressVersionException $exception) {
                // allowed: reader MUST skip over an f field with unknown version
            }
        }

        return $tags;
    }

    protected function getUnknownParser(string $tagCode): \Closure
    {
        return static function ($words) use ($tagCode) {
            return [
                'tag_code' => (int) $tagCode,
                'words' => encode('unknown', $words),
            ];
        };
    }

    protected function tagsContainItem(array $tags, string $tagName): bool
    {
        return null !== $this->tagsItems($tags, $tagName);
    }

    protected function tagsItems(array $tags, string $tagName)
    {
        foreach ($tags as $tag) {
            if ($tagName === $tag['tag_name']) {
                return $tag['data'] ?? null;
            }
        }

        return null;
    }

    /*
    protected function extractVerifyPublicKey(
        int $recoveryID,
        BufferInterface $signatureBuffer,
        BufferInterface $payReqHash,
        array $tags
    ): PublicKeyInterface {
        try {
            // rebuild the signature buffer in a way bit-wasp accepts and knows it (flag+sig), add 27 + 4 (compressed sig).
            $reformattedSignatureBuffer = Buffertools::concat(Buffer::int($recoveryID + 27 + 4), $signatureBuffer);
            $compactSignature = (new CompactSignatureSerializer($this->ecAdapter))->parse($reformattedSignatureBuffer);
            $sigPubkey = $this->ecAdapter->recover($payReqHash, $compactSignature);
        } catch (\Throwable $exception) {
            throw new UnrecoverableSignatureException('unable to recover signature from signed message', $exception->getCode(), $exception);
        }

        if (
            $this->tagsContainItem($tags, $this->tagNames[19])
            && $this->tagsItems(
                $tags,
                $this->tagNames[19]
            ) !== $sigPubkey->getHex()
        ) {
            throw new SignatureDoesNotMatchPayeePubkeyDecodeException('lightning payment request signature pubkey does not match payee pubkey');
        }

        return $sigPubkey;
    }
    */

    protected function wordsToHex(array $words): string
    {
        return $this->wordsToBuffer($words)->getHex();
    }

    protected function wordsToUtf8(array $words): string
    {
        return mb_convert_encoding($this->wordsToBuffer($words)->getBinary(), 'utf-8');
    }

    protected function routingInfoParser(array $words): array
    {
        $routes = [];
        $routesBuffer = $this->wordsToBuffer($words);

        while ($routesBuffer->getSize() > 0) {
            $pubKey = $routesBuffer->slice(0, 33)->getHex(); // 33 bytes
            $shortChannelId = $routesBuffer->slice(33, 8)->getHex(); // 8 bytes
            $feeBaseMSats = (int) $routesBuffer->slice(41, 4)->getInt(); // 4 bytes
            $feeProportionalMillionths = (int) $routesBuffer->slice(45, 4)->getInt(); // 4 bytes
            $cltvExpiryDelta = (int) $routesBuffer->slice(49, 2)->getInt(); // 2 bytes

            $routesBuffer = $routesBuffer->slice(51);

            $routes[] = [
                'pubkey' => $pubKey,
                'short_channel_id' => $shortChannelId,
                'fee_base_msat' => $feeBaseMSats,
                'fee_proportional_millionths' => $feeProportionalMillionths,
                'cltv_expiry_delta' => $cltvExpiryDelta,
            ];
        }

        return $routes;
    }

    protected function fallbackAddressParser(array $words, NetworkInterface $network): array
    {
        $version = $words[0];
        $words = \array_slice($words, 1);
        $addressHash = $this->wordsToBuffer($words);

        switch ($version) {
            case 17:
                $address = (new PayToPubKeyHashAddress($addressHash))->getAddress($network);

                break;

            case 18:
                $address = (new ScriptHashAddress($addressHash))->getAddress($network);

                break;

            case 0:
                $address = (new SegwitAddress(WitnessProgram::v0($addressHash)))->getAddress($network);

                break;

            default:
                throw new UnknownFallbackAddressVersionException('unknown fallback address version ('.$version.') encountered while parsing');
        }

        return [
            'code' => $version,
            'address' => $address,
            'address_hash' => $addressHash->getHex(),
        ];
    }
}
