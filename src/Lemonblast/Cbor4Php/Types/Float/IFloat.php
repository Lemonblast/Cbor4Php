<?php namespace Lemonblast\Cbor4Php\Types\Float;

/**
 * Defines the float interface, so you can call the defined methods from anywhere and they result in different values
 * based on the float type.
 *
 * Interface IFloat
 * @package Lemonblast\Cbor4Php\Types\Float
 */
interface IFloat {

    /**
     * Get the number of exponent bits in this float type.
     *
     * @return int the Number of exponent bits.
     */
    public static function getNumExponentBits();

    /**
     * Get the number of significand bits in this float type.
     *
     * @return int the Number of exponent bits.
     */
    public static function getNumSignificandBits();

    /**
     * Returns the float offset.
     *
     * @return int The offset of the exponent
     */
    public static function getExponentOffset();

    /**
     * Get the additional type for the float.
     *
     * @return int The additional type.
     */
    public static function getAdditionalType();

    /**
     * Decodes the supplied byte array into a float.
     *
     * @param array $encoded The array of encoded bytes.
     * @return float The decoded float.
     */
    public static function decode(array $encoded);
}

?>