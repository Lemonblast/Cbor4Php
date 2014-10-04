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
class Cbor
{
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
            case "boolean":
            case "double":
//                return self::encodeSimple($decoded);

            case "integer":
                return self::encodeInteger($decoded);

            case "array":
                return self::encodeArray($decoded);

            case "string":
                return self::encodeString($decoded);

            case "object":
            case "resource":
            case "NULL":
            case "unknown type":
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

        // Regular ints don't need any conversion
        else
        {
            $major = MajorType::POSITIVE_INT;
        }

        // If it's less than 23, you can just encode with the value as the additional info
        switch(true)
        {
            case $int <= AdditionalType::MAX_VALUE:
                return self::encodeFirstByte($major, $int);

            case $int <= Max::UINT_8:
                return self::encodeFirstByte($major, AdditionalType::UINT_8) . pack(PackFormat::UINT_8, $int);

            case $int <= Max::UINT_16:
                return self::encodeFirstByte($major, AdditionalType::UINT_16) . pack(PackFormat::UINT_16, $int);

            case $int <= Max::UINT_32:
                return self::encodeFirstByte($major, AdditionalType::UINT_32) . pack(PackFormat::UINT_32, $int);

            case $int <= Max::UINT_64:
                return self::encodeFirstByte($major, AdditionalType::UINT_64) . pack(PackFormat::UINT_64, $int >> 32, $int & 0xffffffff);

            default:
                throw new CborException("The input integer is too large to be encoded in CBOR.");
        }
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
        $firstByte = ($major & MajorType::BIT_MASK) | ($additional & AdditionalType::BIT_MASK);
        return pack(PackFormat::UINT_8, $firstByte);
    }
}

?>
