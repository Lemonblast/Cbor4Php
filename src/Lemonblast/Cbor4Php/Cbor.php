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
     * @return mixed Encoded string.
     */
    public static function encode($decoded)
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
                    return self::encodeDouble($decoded);
                }

            case "boolean":
                return self::encodeBoolean($decoded);

            case "string":
                return self::encodeString($decoded);

            case "array":
                // If the array has sequential keys from 0 to n then assume we are dealing with a list
                if (array_keys($decoded) !== range (o, count($decoded) - 1))
                {
                    return self::encodeList($decoded);
                }
                // Otherwise it's a map
                else
                {
                    return self::encodeMap($decoded);
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
     * Encodes an data type length into a CBOR string
     *
     * @param $major_type The MajorType enum to set the first byte.
     * @param $length Length of data to encode.
     * @return string Cbor String.
     * @throws CborException If the length is too large.
     */
    private static function encodeLength($major_type, $length)
    {
        switch(true)
        {
            case $length <= AdditionalType::MAX_VALUE:
                return self::encodeFirstByte($major_type, $length);

            case $length <= Max::UINT_8:
                return self::encodeFirstByte($major_type, AdditionalType::UINT_8) . pack(PackFormat::UINT_8, $length);

            case $length <= Max::UINT_16:
                return self::encodeFirstByte($major_type, AdditionalType::UINT_16) . pack(PackFormat::UINT_16, $length);

            case $length <= Max::UINT_32:
                return self::encodeFirstByte($major_type, AdditionalType::UINT_32) . pack(PackFormat::UINT_32, $length);

            case $length <= Max::UINT_64:
                return self::encodeFirstByte($major_type, AdditionalType::UINT_64) . pack(PackFormat::UINT_64, $length >> 32, $length & 0xffffffff);

            default:
                throw new CborException("Data type length is too long to be encoded in CBOR.");
        }
    }

    /**
     * Encodes an integer into a CBOR string.
     *
     * @param $int Int to encode.
     * @return string Cbor String.
     * @throws CborException If the integer is too large.
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

        if ($int > Max::UINT_64)
        {
            throw new CborException("The input integer is too large to be encoded in CBOR.");
        }

        return self::encodeLength($major, $int);
    }

    private static function encodeDouble($double)
    {
        return null;
    }

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

    private static function encodeString($string)
    {
        $length = mb_strlen($string, self::STRING_ENCODING);

        if ($length > Max::UINT_64)
        {
            throw new CborException("String is too long to be encoded in CBOR.");
        }

        $data = self::encodeLength(MajorType::UTF8_STRING, $length);

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

    private static function encodeList($array)
    {
        return null;
    }

    private static function encodeMap($array)
    {
        return null;
    }

    private static function encodeNull()
    {
        return self::encodeFirstByte(MajorType::SIMPLE_AND_FLOAT, AdditionalType::SIMPLE_NULL);
    }

    private static function encodeUndefined()
    {
        return self::encodeFirstByte(MajorType::SIMPLE_AND_FLOAT, AdditionalType::SIMPLE_UNDEFINED);
    }

    /**
     * Constructs the first byte of a cbor data type, using the major type and additional information.
     *
     * @param $major
     * @param $additional
     * @return string
     */
    private static function encodeFirstByte($major, $additional)
    {
        $first_byte = ($major & MajorType::BIT_MASK) | ($additional & AdditionalType::BIT_MASK);
        return pack(PackFormat::UINT_8, $first_byte);
    }
}

?>
