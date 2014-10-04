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
     * @param $var
     * @throws CborException
     */
    private static function encodeInteger($var)
    {
        // Unsigned ints have a unsigned int major type and need to be converted to abs($val) - 1
        if($var < 0)
        {
            $major = MajorType::NEGATIVE_INT;
            $var = abs($var) - 1;
        }

        // Regular ints don't need any conversion
        else
        {
            $major = MajorType::POSITIVE_INT;
        }

        // If it's less than 23, you can just encode with the value as the additional info
        if($var <= Size::UINT_INLINE)
        {
            return self::makeFirstByte($major, $var);
        }
        else if($var <= Size::UINT_8)
        {
            return self::makeFirstByte($major, AdditionalType::UINT_8) . pack(PackFormat::UINT_8, $var);
        }
        else if($var <= Size::UINT_16)
        {
            return self::makeFirstByte($major, AdditionalType::UINT_16) . pack(PackFormat::UINT_16, $var);
        }
        else if($var <= Size::UINT_32)
        {
            return self::makeFirstByte($major, AdditionalType::UINT_32) . pack(PackFormat::UINT_32, $var);
        }
        else if($var <= Size::UINT_64)
        {
            // Initialize the first byte
            $byteString = self::makeFirstByte($major, AdditionalType::UINT_64);

            // 64 bit values are not supported by pack, split it into 2 32bit packs
            $byteString .= pack(PackFormat::UINT_64, $var >> 32, $var & 0xffffffff);

            return $byteString;
        }
        else
        {
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
