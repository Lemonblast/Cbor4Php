<?php namespace Lemonblast\Cbor4Php\Types\Float;

use Lemonblast\Cbor4Php\Cbor;
use Lemonblast\Cbor4Php\Enums\MajorType;

/**
 * Defines an abstract class for float types, so we have a place for common methods.
 *
 * Abstract Class AbstractFloat
 * @package Lemonblast\Cbor4Php\Types\Float
 */
abstract class AbstractFloat implements IFloat {

    /**
     * Gets the maximum number that can fit in the float implementations exponent.
     *
     * @return int The floats maximum exponent.
     */
    public static function getMaxExponent()
    {
        return pow(2, static::getNumExponentBits()) - 1;
    }

    /**
     * Gets the maximum number that can fit in the float implementations significand.
     *
     * @return int The floats maximum significand.
     */
    public static function getMaxSignificand()
    {
        return pow(2, static::getNumSignificandBits()) - 1;
    }

    /**
     * Determines if the 64 bit float fits in a 16 bit float.
     *
     * @param Float64 $float The float.
     * @return bool True if it fits, false otherwise.
     */
    public static function fits(Float64 $float)
    {
        return $float->isZero() || $float->isInfinity() || $float->isNaN() || static::fitsAsNormal($float) || static::fitsAsSubnormal($float);
    }

    /**
     * Determines if the supplied double fits under normal conditions.
     *
     * @param $float Float64 The 64 bit representation of this float.
     * @return bool True if it fits, false otherwise.
     */
    public static function fitsAsNormal(Float64 $float)
    {
        return ($float->getSignificand() <= self::getMaxSignificand()) && (self::exponentFits($float->getExponent()));
    }

    /**
     * Determines if the supplied double fits into this float as a subnormal.
     *
     * @param Float64 $float The float (represented in 64 bit).
     * @return boolean Whether the double fits as a subnormal inside this float type.
     */
    public static function fitsAsSubnormal(Float64 $float)
    {
        // If the exponent is lower than the lowest subnormal, it can't fit
        if ($float->getExponent() < (1 - static::getExponentOffset() - static::getNumSignificandBits()))
        {
            return false;
        }

        // If the exponent is larger than the negative exponent offset, it can't fit
        if($float->getExponent() > (1 - static::getExponentOffset()))
        {
            return false;
        }

        // Make sure the significand (+1 implicit digit) actually fits in the required number of bits
        if ((($float->getSignificand() << 1) + 1) > self::getMaxSignificand())
        {
            return false;
        }

        // Trash all the trailing zeros in the significand
        $significand = $float->getSignificand();

        // Convert the significand into a binary string
        $significand = decbin($significand);

        // Trim leading zeros
        $significand = ltrim($significand, '0');

        // Add a 1 to the start of the string, it's implicit in normal numbers, but not in subnormals
        $significand = bindec('1' . $significand);

        // Determine the number of "exponent bits" you have free
        $exponentBits = static::getNumSignificandBits() - strlen($significand);

        // Compute the minimum exponent you can do with those free bits
        $minExponent = -1 * ((static::getExponentOffset()) + $exponentBits);

        // If the exponent fits
        return $float->getExponent() >= $minExponent;
    }

    /**
     * Reverses an array or string if the system is little endian.
     *
     * @param $toCheck array|string The array/string;
     * @return array|string The original array/string if on a big endian system, otherwise a reversed version.
     */
    public static function reverseIfLittleEndian($toCheck)
    {
        // Little endian, needs to be reversed
        if (unpack('S',"\x01\x00")[1] === 1)
        {
            // Reverse an array
            if (is_array($toCheck))
            {
                return array_reverse($toCheck);
            }

            // Reverse a string
            else
            {
                return strrev($toCheck);
            }
        }

        // Big endian, no need to reverse
        else
        {
            return $toCheck;
        }
    }

    /**
     * Determines if the exponent fits into this size of float.
     *
     * @param $exponent int The exponent to check.
     * @return bool True if it fits, false otherwise.
     */
    public static function exponentFits($exponent)
    {
        // Determine if the exponent fits
        $max = floor((pow(2, static::getNumExponentBits()) - 1) / 2);
        $min = (-1 * $max) + 1;

        return (($exponent >= $min) && ($exponent <= $max));
    }

    /**
     * Determines the number of trailing zeros in a number.
     *
     * @param $number int The number.
     * @return int The number of trailing zeros, or -1 if the number is 0.
     */
    protected static function trailingZeros($number)
    {
        // The number is zero, all of it is trailing zeros
        if ($number == 0)
        {
            return -1;
        }

        $count = 0;

        while (!($number & 0b1))
        {
            $number = $number >> 1;
            $count++;
        }

        return $count;
    }

    /**
     * Gets the number of leading zeros in a significand.
     *
     * @param $significand int The significand.
     * @return int The number of leading zeros.
     */
    protected static function leadingZeros($significand)
    {
        // Convert to binary string
        $significand = decbin($significand);

        // Trim all the zeros from the left
        $significand = ltrim($significand, '0');

        return static::getNumSignificandBits() - strlen($significand);
    }

    /**
     * Encodes the first byte, with an additional type that is dependent on the float type.
     *
     * @return string The encoded byte.
     */
    protected static function encodeFirstByte()
    {
        return Cbor::encodeFirstByte(MajorType::SIMPLE_AND_FLOAT, static::getAdditionalType());
    }

    /**
     * Pads a significand on the right so it's the correct length.
     *
     * @param $significand mixed The significand.
     * @return mixed The padded significand.
     */
    protected static function padSignificand($significand)
    {
        if(is_string($significand))
        {
            return str_pad($significand, static::getNumSignificandBits(), '0');
        }
        else
        {
            return bindec(str_pad(decbin($significand), static::getNumSignificandBits(), '0'));
        }
    }

    /**
     * Converts the supplied byte array to a string.
     *
     * @param array $byteArray The byte array.
     * @return string The string.
     */
    protected static function convertToString(array $byteArray)
    {
        // Convert the bytes to a string
        $string = '';
        foreach ($byteArray as $byte)
        {
            $string .= chr($byte);
        }

        return $string;
    }
}

?>