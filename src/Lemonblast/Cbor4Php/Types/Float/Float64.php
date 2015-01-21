<?php namespace Lemonblast\Cbor4Php\Types\Float;

use Lemonblast\Cbor4Php\Enums\PackFormat;

/**
 * Defines constants and logic for 64 bit floats.
 *
 * Class Float64
 * @package Lemonblast\Cbor4Php\Types\Float
 */
class Float64 extends AbstractFloat {

    /** The number of bits in the exponent. */
    const EXPONENT_LENGTH = 11;

    /** The number of bits in the significand. */
    const SIGNIFICAND_LENGTH = 52;

    /** The exponent offset. */
    const EXPONENT_OFFSET = 1023;

    /** The CBOR additional type. */
    const ADDITIONAL_TYPE = 27;

    /** @var float The original double value. */
    private $double;

    /** @var int The doubles sign. */
    private $sign;

    /** @var int The doubles significand. */
    private $significand;

    /** @var int The doubles exponent. */
    private $exponent;

    /** @var int The simplified significand. */
    private $realSignificand;

    /** @var int The real exponent (calculated from the raw one). */
    private $realExponent;

    /**
     * Construct a 64 bit float from the specified double.
     *
     * @param $double float The double.
     */
    public function __construct($double)
    {
        // Save the double
        $this->double = $double;

        // Get a byte string as a 64 bit double for maximum accuracy
        $string = pack(PackFormat::FLOAT_64, $double);

        // Convert to byte array
        $byte_array = unpack(PackFormat::UNIT_8_SET, $string);

        // Reverse it on little endian systems, you want MSB first
        $bytes = static::reverseIfLittleEndian($byte_array);

        // Get parameters
        $this->sign = ($bytes[0] >> 7) & 0b1;                                         // Sign is the first bit
        $this->exponent = (((($bytes[0]) & 0b1111111) << 4) | ($bytes[1] >> 4));   // Next 11 are the exponent

        // Make the significand (Final 52 bits)
        $this->significand = ($bytes[1] & 0b1111) << (self::getNumSignificandBits() - 4);
        for ($i = 2; $i < 8; $i++)
        {
            $this->significand += ($bytes[$i] << ((7 - $i) * 8));
        }

        // Save the real exponent (taking subnormals into account)
        if($this->exponent == 0 && $this->significand != 0)
        {
            // Subnormal, real exponent is 1-offset - number of free bits
            $this->realExponent = 1 - self::getExponentOffset() - static::leadingZeros($this->significand);
        }
        else
        {
            // Regular number
            $this->realExponent = $this->exponent - self::getExponentOffset();
        }

        // Scrape off trailing zeros
        $this->realSignificand = $this->significand >> static::trailingZeros($this->significand);
    }

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
     * Encodes a double as a 64 bit float.
     *
     * @param $double double The double to encode.
     * @return array|string The encoded double.
     */
    public static function encode($double)
    {
        return static::encodeFirstByte() . static::reverseIfLittleEndian(pack(PackFormat::FLOAT_64, $double));
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

        $doubles = unpack(PackFormat::FLOAT_64, static::convertToString($encoded));
        return array_shift($doubles);
    }

    /**
     * Determines if the float is zero.
     *
     * @return bool True if zero, false otherwise.
     */
    public function isZero()
    {
        return $this->exponent == 0 && $this->significand == 0;
    }

    /**
     * Determines if the float is infinity.
     *
     * @return bool True if infinity, false otherwise.
     */
    public function isInfinity()
    {
        return $this->exponent == static::getMaxExponent() && $this->significand == 0;
    }

    /**
     * Determines if the float is NaN.
     *
     * @return bool True if NaN, false otherwise.
     */
    public function isNaN()
    {
        return $this->exponent == static::getMaxExponent() && $this->significand != 0;
    }

    /**
     * Gets the real exponent from the float.
     *
     * @return int The exponent.
     */
    public function getExponent()
    {
        return $this->realExponent;
    }

    /**
     * Gets the real significand from the float.
     *
     * @return int The significand.
     */
    public function getSignificand()
    {
        return $this->realSignificand;
    }

    /**
     * Gets the floats sign.
     *
     * @return int 1 if negative, 0 otherwise.
     */
    public function getSign()
    {
        return $this->sign > 0 ? 1 : 0;
    }
}

?>