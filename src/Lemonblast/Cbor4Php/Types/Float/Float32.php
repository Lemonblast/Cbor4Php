<?php namespace Lemonblast\Cbor4Php\Types\Float;

use Lemonblast\Cbor4Php\Enums\PackFormat;

/**
 * Defines constants and logic for 32 bit floats.
 *
 * Class Float32
 * @package Lemonblast\Cbor4Php\Float
 */
class Float32 extends AbstractFloat {

    /** The number of bits in the exponent. */
    const EXPONENT_LENGTH = 8;

    /** The number of bits in the significand. */
    const SIGNIFICAND_LENGTH = 23;

    /** The exponent offset. */
    const EXPONENT_OFFSET = 127;

    /** The CBOR additional type. */
    const ADDITIONAL_TYPE = 26;

    /**
     * Get the number of exponent bits in this float type.
     *
     * @return int the Number of exponent bits.
     */
    public static function getNumExponentBits()
    {
        return self::EXPONENT_LENGTH;
    }

    /**
     * Get the number of significand bits in this float type.
     *
     * @return int the Number of exponent bits.
     */
    public static function getNumSignificandBits()
    {
        return self::SIGNIFICAND_LENGTH;
    }

    /**
     * Returns the float offset.
     *
     * @return int The offset of the exponent
     */
    public static function getExponentOffset()
    {
        return self::EXPONENT_OFFSET;
    }

    /**
     * Get the additional type for the float.
     *
     * @return int The additional type.
     */
    public static function getAdditionalType()
    {
        return self::ADDITIONAL_TYPE;
    }

    /**
     * Encodes a double as a 32 bit float.
     *
     * @param $double double The double to encode.
     * @return array|string The encoded double.
     */
    public static function encode($double)
    {
        return static::encodeFirstByte() . static::reverseIfLittleEndian(pack(PackFormat::FLOAT_32, $double));
    }

    /**
     * Decodes the supplied byte array into a float.
     *
     * @param array $encoded The array of encoded bytes.
     * @return float The decoded float.
     */
    public static function decode(array $encoded)
    {
        $encoded = static::reverseIfLittleEndian($encoded);

        $doubles = unpack(PackFormat::FLOAT_32, static::convertToString($encoded));
        return array_shift($doubles);
    }
}

?>