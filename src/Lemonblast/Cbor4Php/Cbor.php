<?php namespace Lemonblast\Cbor4Php;

use Lemonblast\Cbor4Php\Enums\AdditionalType;
use Lemonblast\Cbor4Php\Enums\MajorType;
use Lemonblast\Cbor4Php\Enums\PackFormat;
use Lemonblast\Cbor4Php\Enums\Max;

/**
 * Manages encoding and decoding of CBOR data.
 *
 * Class Cbor
 * @package Lemonblast\Cbor4Php
 */
class Cbor {

    const STRING_ENCODING = "UTF-8";

    /**
     * Encodes the supplied value into a CBOR string.
     *
     * @param mixed $decoded Data to encode.
     * @param bool $float_64 Optional flag to force 64 bit encoding for floats.
     * @throws CborException
     * @return mixed Encoded string.
     */
    public static function encode($decoded, $float_64 = false)
    {
        switch(gettype($decoded))
        {
            case "integer":
                return self::encodeInteger($decoded);

            case "double":
                // Extremely large ints return "double" due to a bug, this makes sure it won't happen
                if(floor($decoded) == $decoded)
                {
                    return self::encodeInteger($decoded);
                }
                // Otherwise, it's a float
                else
                {
                    return self::encodeDouble($decoded, $float_64);
                }

            case "boolean":
                return self::encodeBoolean($decoded);

            case "string":
                return self::encodeString($decoded);

            case "array":
                // If the array doesn't have sequential keys from 0 to n then assume we are dealing with a map
                if (array_keys($decoded) !== range (0, count($decoded) - 1))
                {
                    return self::encodeMap($decoded);
                }
                // Otherwise it's a sequence
                else
                {
                    return self::encodeSequence($decoded);
                }

            case "NULL":
                return self::encodeNull();

            case "unknown type":
                return self::encodeUndefined();

            case "object":
            case "resource":
            default:
                return null;
        }
    }

    /**
     * Decodes the supplied CBOR string.
     *
     * @param string $encoded Data to decode.
     */
    public static function decode($encoded)
    {
        return null;
    }

    /**
     * Encodes a value into a CBOR string.
     *
     * @param int $major_type The MajorType enum to set the first byte.
     * @param int $value Value to encode.
     * @throws CborException
     * @return string The encoded byte string.
     */
    private static function encodeIntValue($major_type, $value)
    {
        switch(true)
        {
            case $value <= AdditionalType::MAX_VALUE:
                return self::encodeFirstByte($major_type, $value);

            case $value <= Max::UINT_8:
                return self::encodeFirstByte($major_type, AdditionalType::UINT_8) . pack(PackFormat::UINT_8, $value);

            case $value <= Max::UINT_16:
                return self::encodeFirstByte($major_type, AdditionalType::UINT_16) . pack(PackFormat::UINT_16, $value);

            case $value <= Max::UINT_32:
                return self::encodeFirstByte($major_type, AdditionalType::UINT_32) . pack(PackFormat::UINT_32, $value);

            case $value <= Max::UINT_64:
                return self::encodeFirstByte($major_type, AdditionalType::UINT_64) . pack(PackFormat::UINT_64, $value >> 32, $value & 0xffffffff);

            default:
                throw new CborException("Value is too large to be encoded in CBOR.");
        }
    }

    /**
     * Encodes an integer into a CBOR string.
     *
     * @param int $int Int to encode.
     * @return string The encoded byte string.
     */
    private static function encodeInteger($int)
    {
        // Unsigned ints have a unsigned int major type and need to be converted to abs($val) - 1
        if($int < 0)
        {
            $major = MajorType::NEGATIVE_INT;
            $int = abs($int) - 1;
        }
        else
        {
            $major = MajorType::POSITIVE_INT;
        }

        return self::encodeIntValue($major, $int);
    }

    /**
     * Encodes a double into a CBOR string.
     *
     * @param float $double The double to encode.
     * @param bool $float_64 Optional flag to force 64 bit encoding.
     * @return string The encoded byte string.
     */
    private static function encodeDouble($double, $float_64 = false)
    {
        $major = MajorType::SIMPLE_AND_FLOAT;

        // If the encode doubles in 64 bit flag is set
        if($float_64)
        {
            return self::encodeFirstByte($major, AdditionalType::FLOAT_64) . strrev(pack(PackFormat::FLOAT_64, $double));
        }

        // Default to 32 bit (16 is not supported by PHP)
        return self::encodeFirstByte($major, AdditionalType::FLOAT_32) . strrev(pack(PackFormat::FLOAT_32, $double));
    }

    /**
     * Encodes a boolean value.
     *
     * @param boolean $bool The boolean to encode.
     * @return string The encoded byte string.
     */
    private static function encodeBoolean($bool)
    {
        if ($bool)
        {
            return self::encodeFirstByte(MajorType::SIMPLE_AND_FLOAT, AdditionalType::SIMPLE_TRUE);
        }
        else
        {
            return self::encodeFirstByte(MajorType::SIMPLE_AND_FLOAT, AdditionalType::SIMPLE_FALSE);
        }
    }

    /**
     * Encodes a string.
     *
     * @param string $string String to encode.
     * @return string The encoded byte string.
     * @throws CborException If the string is too long to be encoded in CBOR.
     */
    private static function encodeString($string)
    {
        $length = mb_strlen($string, self::STRING_ENCODING);

        if ($length > Max::UINT_64)
        {
            throw new CborException("String is too long to be encoded in CBOR.");
        }

        $data = self::encodeIntValue(MajorType::UTF8_STRING, $length);

        for ($i = 0; $i < $length; $i++)
        {
            $mbchar = mb_substr($string, $i, 1);
            $chars = strlen($mbchar);
            for ($j = 0; $j < $chars; $j++)
            {
                $data .= pack(PackFormat::UINT_8, ord($mbchar[$j]));
            }
        }

        return $data;
    }

    /**
     * Encodes a sequence (array).
     *
     * @param array $sequence The array to encode.
     * @return string The encoded byte string.
     * @throws CborException If the array is too long to be encoded in CBOR.
     */
    private static function encodeSequence($sequence)
    {
        $length = count($sequence);

        if ($length > Max::UINT_64)
        {
            throw new CborException("Array is too long to be encoded in CBOR.");
        }

        // Encode the length
        $data = self::encodeIntValue(MajorType::SEQUENCE, $length);

        // Encode each item and append to output
        foreach ($sequence as $item)
        {
            $data .= self::encode($item);
        }

        return $data;
    }

    /**
     * Encodes a map (associative array).
     *
     * @param array $array The array to encode.
     * @return string The encoded byte string.
     * @throws CborException If the array is too long to be encoded in CBOR.
     */
    private static function encodeMap($array)
    {
        $length = count($array);

        if ($length > Max::UINT_64)
        {
            throw new CborException("Array is too long to be encoded in CBOR.");
        }

        // Encode the length
        $data = self::encodeIntValue(MajorType::MAP, $length);

        // Encode each key-value pair
        foreach ($array as $key => $value)
        {
            $data .= self::encode($key);
            $data .= self::encode($value);
        }

        return $data;
    }

    /**
     * Encodes a null value.
     *
     * @return string The encoded byte string.
     */
    private static function encodeNull()
    {
        return self::encodeFirstByte(MajorType::SIMPLE_AND_FLOAT, AdditionalType::SIMPLE_NULL);
    }

    /**
     * Encodes a undefined value.
     *
     * @return string The encoded byte string.
     */
    private static function encodeUndefined()
    {
        return self::encodeFirstByte(MajorType::SIMPLE_AND_FLOAT, AdditionalType::SIMPLE_UNDEFINED);
    }

    /**
     * Constructs the first byte of a CBOR data type, using the major type and additional information.
     *
     * @param int $major The major type to use.
     * @param int $additional The additional type to use.
     * @return string The encoded byte string.
     */
    private static function encodeFirstByte($major, $additional)
    {
        $first_byte = ($major & MajorType::BIT_MASK) | ($additional & AdditionalType::BIT_MASK);
        return pack(PackFormat::UINT_8, $first_byte);
    }
}

?>
