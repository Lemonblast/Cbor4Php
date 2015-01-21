<?php namespace Lemonblast\Cbor4Php\Types\Float;

use Lemonblast\Cbor4Php\Enums\PackFormat;

/**
 * Defines constants and logic for 16 bit floats.
 *
 * Class Float16
 * @package Lemonblast\Cbor4Php\Float
 */
class Float16 extends AbstractFloat {

    /** The number of bits in the exponent. */
    const EXPONENT_LENGTH = 5;

    /** The number of bits in the significand. */
    const SIGNIFICAND_LENGTH = 10;

    /** The exponent offset. */
    const EXPONENT_OFFSET = 15;

    /** The CBOR additional type. */
    const ADDITIONAL_TYPE = 25;

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
     * Encodes a 64 bit float into a 16 bit float CBOR byte string.
     *
     * @param $float Float64 The 64 bit representation.
     * @return string int The Encoded byte string.
     */
    public static function encode(Float64 $float)
    {
        if ($float->isZero())
        {
            $sign = 0;
            $exponent = 0;
            $significand = 0;
        }

        else if ($float->isInfinity())
        {
            $sign = $float->getSign();
            $exponent = static::getMaxExponent();
            $significand = 0;
        }

        else if ($float->isNaN())
        {
            $sign = 0;
            $exponent = static::getMaxExponent();
            $significand = static::padSignificand(1);
        }

        // Value fits as a normal 16 bit float
        else if (static::fitsAsNormal($float))
        {
            $sign = $float->getSign();
            $exponent = $float->getExponent() + self::getExponentOffset();
            $significand = static::padSignificand($float->getSignificand());
        }

        // Must be a subnormal
        else
        {
            $sign = $float->getSign();
            $exponent = 0;

            // Grab the significand without the leading zeros
            $significand = $float->getSignificand();

            // Convert the significand into a binary string
            $significand = decbin($significand);

            // Trim leading zeros
            $significand = ltrim($significand, '0');

            // Add a 1 to the start of the string, it's implicit in normal numbers, but not in subnormals
            $significand = '1' . $significand;

            // Get the distance the first significant digit should be from the 0 point
            $exponentAddOn = abs($float->getExponent() + self::getExponentOffset());

            // Add that many zeros on the left
            $significand = str_repeat('0', $exponentAddOn) . $significand;

            // Pad the rest with zeros on the right and convert to decimal
            $significand = bindec(static::padSignificand($significand));
        }

        // Make second byte
        $second = (($sign << 7) & 0b10000000) | (($exponent << 2) & 0b01111100) | (($significand >> 8) & 0b11);

        // And the third
        $third = $significand & 255;

        return static::encodeFirstByte() . pack(PackFormat::UINT_8, $second) . pack(PackFormat::UINT_8, $third);
    }

    /**
     * Decodes the supplied byte array into a float.
     *
     * @param array $encoded The array of encoded bytes.
     * @return float The decoded float.
     */
    public static function decode(array $encoded)
    {
        // Grab both bytes
        $msb = array_shift($encoded);
        $lsb = array_shift($encoded);

        // Get the components of the double
        $sign = ($msb >> 7) & 0b1;                  // Sign is the first bit
        $exponent = ($msb >> 2) & 0b11111;          // Next 5 are the exponent
        $significand = $lsb | (($msb & 0b11) << 8); // Final 10 are the significand

        // Convert the significand to a float
        $decimal = 0;
        for ($i = 9; $i >= 0; $i--)
        {
            if (($significand >> ($i)) & 0b1)
            {
                $decimal += pow(2, -1 * (10 - $i));
            }
        }

        // If it's a signed zero
        if ($exponent == 0 && $significand == 0)
        {
            return 0.0;
        }

        // It's a subnormal
        else if ($exponent == 0)
        {
            $double = pow(-1, $sign) * pow(2, 1 - self::getExponentOffset()) * $decimal;
        }

        // It's a signed infinity or NaN
        else if ($exponent == static::getMaxExponent())
        {
            // Signed infinity
            if ($significand == 0)
            {
                if ($sign == 0)
                {
                    return INF;
                }
                else
                {
                    return -INF;
                }
            }

            // It's a NaN
            else
            {
                return NAN;
            }
        }

        // Regular float
        else
        {
            $double = pow(-1, $sign) * pow(2, $exponent - self::getExponentOffset()) * (1 + $decimal);
        }

        return $double;
    }
}

?>