<?php

namespace Cryptomute;

use InvalidArgumentException;
use LogicException;

/**
 * Cryptomute.
 *
 * (c) 2016 Piotr Gołębiewski
 *
 * Released under MIT license.
 * https://github.com/loostro/cryptomute
 *
 * Format preserving data encryption and decryption based on feistel network.
 */
class Cryptomute
{
    public const KEY_MIN_LENGTH = 16;
    public const MIN_ROUNDS = 3;
    public const DEFAULT_MIN_VALUE = '0';
    public const DEFAULT_MAX_VALUE = '99999999999999999999'; // 20 digits
    /**
     * @var array
     */
    public static array $allowedCiphers = [
        'aes-128-cbc' => ['iv' => true, 'length' => 128],
        'aes-192-cbc' => ['iv' => true, 'length' => 192],
        'aes-256-cbc' => ['iv' => true, 'length' => 256],
        'camellia-128-cbc' => ['iv' => true, 'length' => 128],
        'camellia-192-cbc' => ['iv' => true, 'length' => 192],
        'camellia-256-cbc' => ['iv' => true, 'length' => 256],
    ];
    /**
     * @var array
     */
    public static array $allowedBases = [
        2 => '/^[0-1]+$/',
        10 => '/^[0-9]+$/',
        16 => '/^[a-f0-9]+$/',
    ];
    /**
     * @var string|null
     */
    private ?string $minValue;
    /**
     * @var string|null
     */
    private ?string $maxValue;
    /**
     * @var string
     */
    private string $cipher;
    /**
     * @var string
     */
    private string $key;
    /**
     * @var array|null
     */
    private ?array $roundKeys;
    /**
     * @var string|null
     */
    private ?string $iv;
    /**
     * @var int
     */
    private int $rounds;
    /**
     * @var int|null
     */
    private ?int $roundscipherLength;
    /**
     * @var int|null
     */
    private ?int $roundblockSize;
    /**
     * @var int|null
     */
    private mixed $binSize;
    /**
     * @var int|null
     */
    private mixed $decSize;
    /**
     * @var int|null
     */
    private mixed $hexSize;
    /**
     * @var int|null
     */
    private mixed $sideSize;
    /**
     * @var mixed
     */
    private mixed $cipherLength;

    /**
     * Cryptomute constructor.
     *
     * @param  string  $cipher Cipher used to encrypt.
     * @param  string  $baseKey Base key, from which all round keys are derrived.
     * @param  int  $rounds Number of rounds.
     *
     * @throws InvalidArgumentException If provided invalid constructor parameters.
     * @throws LogicException           If side size is longer than cipher length.
     */
    public function __construct(string $cipher, string $baseKey, int $rounds = 3)
    {
        if (!array_key_exists($cipher, self::$allowedCiphers)) {
            throw new InvalidArgumentException(sprintf(
                'Cipher must be one of "%s".',
                implode(', ', array_keys(self::$allowedCiphers))
            ));
        }

        $this->cipher = $cipher;
        $this->cipherLength = self::$allowedCiphers[$cipher]['length'];
        $this->setValueRange(self::DEFAULT_MIN_VALUE, self::DEFAULT_MAX_VALUE);

        if (!is_int($rounds) || $rounds < self::MIN_ROUNDS || $rounds % 2 !== 1) {
            throw new InvalidArgumentException(sprintf(
                'Number of rounds must be an odd integer greater or equal %d',
                self::MIN_ROUNDS
            ));
        }

        $this->rounds = $rounds;

        if (strlen($baseKey) < self::KEY_MIN_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Key must be at least %d characters long.',
                self::KEY_MIN_LENGTH
            ));
        }

        $this->key = $baseKey;
    }

    /**
     * Set value range and pad sizes.
     *
     * @param  string  $minValue Minimum value. String representation of positive integer value or zero.
     * @param  string  $maxValue Maximum value. String representation of positive integer value.
     *
     * @return Cryptomute
     * @throws InvalidArgumentException If provided invalid parameters.
     */
    public function setValueRange(string $minValue, string $maxValue): self
    {
        if (preg_match('/^([1-9][0-9]*)|([0]{1})$/', $minValue) !== 1) {
            throw new InvalidArgumentException(
                'Min value must contain only digits.'
            );
        }

        if (preg_match('/^[1-9][0-9]*$/', $maxValue) !== 1) {
            throw new InvalidArgumentException(
                'Max value must start with a nonzero digit and contain only digits.'
            );
        }

        if (gmp_cmp($maxValue, $minValue) < 1) {
            throw new InvalidArgumentException('Max value must be greater than min value.');
        }

        $this->minValue = $minValue;
        $this->maxValue = $maxValue;

        // find the minimum number of even bits to span whole set
        $this->binSize = 2;
        $span = gmp_init('4', 10);
        $multiplier = gmp_init('4', 10);
        do {
            $this->binSize += 2;
            $span = gmp_mul($span, $multiplier);
        } while (gmp_cmp($span, $this->maxValue) < 1);

        $this->decSize = strlen($this->maxValue);
        $this->hexSize = $this->binSize / 4;

        $this->sideSize = $this->binSize / 2;

        if ($this->sideSize > $this->cipherLength) {
            throw new LogicException(sprintf(
                'Side size (%d bits) must be less or equal to cipher length (%d bits)',
                $this->sideSize,
                $this->cipherLength
            ));
        }

        return $this;
    }

    /**
     * Encrypts input data. Acts as a public alias for _encryptInternal method.
     *
     * @param  string  $input String representation of input number.
     * @param  int  $base Input number base.
     * @param  bool  $pad Pad left with zeroes?
     * @param  string|null  $password Encryption password.
     * @param  string|null  $iv Encryption initialization vector. Must be unique!
     *
     * @return string Outputs encrypted data in the same format as input data.
     */
    public function encrypt(string $input, int $base = 10, bool $pad = false, string $password = null, string $iv = null): string
    {
        return $this->_encryptInternal($input, $base, $pad, $password, $iv, true);
    }

    /**
     * Encrypts input data.
     *
     * @param  string  $input String representation of input number.
     * @param  int  $base Input number base.
     * @param  bool  $pad Pad left with zeroes?
     * @param  string  $password Encryption password.
     * @param  string  $iv Encryption initialization vector. Must be unique!
     * @param  bool  $checkVal Should check if input value is in range?
     *
     * @return string Outputs encrypted data in the same format as input data.
     */
    private function _encryptInternal(string $input, int $base, bool $pad, string $password, string $iv, bool $checkVal = false): string
    {
        $this->_validateInput($input, $base, $checkVal);
        $this->_validateIv($iv);
        $hashPassword = $this->_hashPassword($password);
        $roundKeys = $this->_roundKeys($hashPassword, $iv);

        $binary = $this->_convertToBin($input, $base);

        for ($i = 1; $i <= $this->rounds; $i++) {
            $left = substr($binary, 0, $this->sideSize);
            $right = substr($binary, -1 * $this->sideSize);

            $key = $roundKeys[$i];
            $round = $this->_round($right, $key, $hashPassword, $iv);

            $newLeft = $right;
            $newRight = $this->_binaryXor($left, $round);

            $binary = $newLeft . $newRight;
        }

        $output = $this->_convertFromBin($binary, $base, $pad);
        $compare = DataConverter::binToDec($binary);

        return (gmp_cmp($this->minValue, $compare) > 0 || gmp_cmp($compare, $this->maxValue) > 0)
            ? $this->_encryptInternal($output, $base, $pad, $password, $iv, false)
            : $output;
    }

    /**
     * Decrypts input data.
     *
     * @param  string  $input Encrypted input.
     * @param  int  $base Input data base.
     * @param  bool  $pad Pad left with zeroes?
     * @param  string|null  $password Decryption password.
     * @param  string|null  $iv Decryption initialization vector.
     *
     * @return string Outputs encrypted data in the same format as input data.
     */
    public function decrypt(string $input, int $base = 10, bool $pad = false, string $password = null, string $iv = null): string
    {
        $this->_validateInput($input, $base);
        $this->_validateIv($iv);
        $hashPassword = $this->_hashPassword($password);
        $roundKeys = $this->_roundKeys($hashPassword, $iv);

        $binary = $this->_convertToBin($input, $base);

        for ($i = $this->rounds; $i > 0; $i--) {
            $left = substr($binary, 0, $this->sideSize);
            $right = substr($binary, -1 * $this->sideSize);

            $key = $roundKeys[$i];
            $round = $this->_round($left, $key, $hashPassword, $iv);

            $newLeft = $this->_binaryXor($right, $round);
            $newRight = $left;

            $binary = $newLeft . $newRight;
        }

        $output = $this->_convertFromBin($binary, $base, $pad);
        $compare = DataConverter::binToDec($binary);

        return (gmp_cmp($this->minValue, $compare) > 0 || gmp_cmp($compare, $this->maxValue) > 0)
            ? $this->decrypt($output, $base, $pad, $password, $iv)
            : $output;
    }

    /**
     * Encrypt helper.
     *
     * @param  string  $input
     * @param  string  $password
     * @param  string  $iv
     *
     * @return string Steam of encrypted bytes.
     */
    private function _encrypt(string $input, string $password, string $iv): string
    {
        return openssl_encrypt($input, $this->cipher, $password, true, $iv);
    }

    /**
     * Round function helper.
     *
     * @param  string  $input
     * @param  string  $key
     * @param  string  $hashPassword
     * @param  string  $iv
     *
     * @return string Binary string.
     */
    private function _round(string $input, string $key, string $hashPassword, string $iv): string
    {
        $bin = DataConverter::rawToBin($this->_encrypt($input . $key, $hashPassword, $iv));

        return substr($bin, -1 * $this->sideSize);
    }

    /**
     * Binary xor helper.
     *
     * @param  string  $left
     * @param  string  $round
     *
     * @return string Binary string.
     */
    private function _binaryXor(string $left, string $round): string
    {
        $xOr = gmp_xor(
            gmp_init($left, 2),
            gmp_init($round, 2)
        );

        $bin = gmp_strval($xOr, 2);

        return str_pad($bin, $this->sideSize, '0', STR_PAD_LEFT);
    }

    /**
     * Helper method converting input data to binary string.
     *
     * @param  string  $input
     * @param  string  $base
     *
     * @return string
     */
    private function _convertToBin(string $input, string $base): string
    {
        switch ($base) {
            case 2:
                return DataConverter::pad($input, $this->binSize);
            case 10:
                return DataConverter::decToBin($input, $this->binSize);
            case 16:
                return DataConverter::hexToBin($input, $this->binSize);
        }
    }

    /**
     * Helper method converting input data from binary string.
     *
     * @param  string  $binary
     * @param  string  $base
     * @param  string  $pad
     *
     * @return string
     */
    private function _convertFromBin(string $binary, string $base, string $pad): string
    {
        switch ($base) {
            case 2:
                return DataConverter::pad($binary, ($pad ? $this->binSize : 0));
            case 10:
                return DataConverter::binToDec($binary, ($pad ? $this->decSize : 0));
            case 16:
                return DataConverter::binToHex($binary, ($pad ? $this->hexSize : 0));
        }
    }

    /**
     * Validates input data.
     *
     * @param  string  $input
     * @param  string  $base
     * @param  bool  $checkDomain Should check if input is in domain?
     *
     * @throws InvalidArgumentException If provided invalid type.
     */
    private function _validateInput(string $input, string $base, bool $checkDomain = false): void
    {
        if (!array_key_exists($base, self::$allowedBases)) {
            throw new InvalidArgumentException(sprintf(
                'Type must be one of "%s".',
                implode(', ', array_keys(self::$allowedBases))
            ));
        }

        if (preg_match(self::$allowedBases[$base], $input) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'Input data "%s" does not match pattern "%s".',
                $input,
                self::$allowedBases[$base]
            ));
        }

        if ($checkDomain) {
            $compare = gmp_init($input, $base);

            if (gmp_cmp($this->minValue, $compare) > 0 || gmp_cmp($compare, $this->maxValue) > 0) {
                throw new InvalidArgumentException(sprintf(
                    'Input value "%d" is out of domain range "%d - %d".',
                    gmp_strval($compare, 10),
                    $this->minValue,
                    $this->maxValue
                ));
            }
        }
    }

    /**
     * Validates initialization vector.
     *
     * @param  string|null  $iv
     */
    private function _validateIv(string $iv = null): void
    {
        if (self::$allowedCiphers[$this->cipher]['iv']) {
            $blockSize = openssl_cipher_iv_length($this->cipher);

            $ivLength = mb_strlen($iv, '8bit');
            if ($ivLength !== $blockSize) {
                throw new InvalidArgumentException(sprintf(
                    'Initialization vector of %d bytes is required for cipher "%s", %d given.',
                    $blockSize,
                    $this->cipher,
                    $ivLength
                ));
            }
        }
    }

    /**
     * Hashes the password.
     *
     * @param  string  $password
     *
     * @return string
     */
    private function _hashPassword(string $password): string
    {
        return hash('sha3-512', $password);
    }

    /**
     * Generates hash keys.
     *
     * @param  string  $hashPassword
     * @param  string  $iv
     *
     * @return array
     */
    private function _roundKeys(string $hashPassword, string $iv): array
    {
        $roundKeys = [];
        $prevKey = $this->_encrypt($this->key, $hashPassword, $iv);
        for ($i = 1; $i <= $this->rounds; $i++) {
            $prevKey = $this->_encrypt($prevKey, $hashPassword, $iv);
            $roundKeys[$i] = substr(DataConverter::rawToBin($prevKey), -1 * $this->sideSize);
        }

        return $roundKeys;
    }
}
