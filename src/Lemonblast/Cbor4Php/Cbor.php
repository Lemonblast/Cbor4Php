<?php namespace Lemonblast\Cbor4Php;

use Lemonblast\Cbor4Php\Enums\AdditionalType;
use Lemonblast\Cbor4Php\Types\Float\Float16;
use Lemonblast\Cbor4Php\Types\Float\Float32;
use Lemonblast\Cbor4Php\Types\Float\Float64;
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

    /** The string encoding to use. */
    const STRING_ENCODING = "UTF-8";

    /**
     * Encodes the supplied value into a CBOR string.
     *
     * @param mixed $decoded Data to encode.
     * @throws CborException
     * @return mixed Encoded string.
     */
    public static function encode($decoded)
    {
        switch(gettype($decoded))
        {
            case "integer":
                return self::encodeInteger($decoded);

            case "double":
                return self::encodeDouble($decoded);

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
                return self::encode(self::objectToMap($decoded));
        }

        // Unknown type, just return null
        return null;
    }

    /**
     * Decodes the supplied CBOR string.
     *
     * @param string $encoded Data to decode.
     * @throws CborException If there are bytes left over after decoding.
     * @return mixed Decoded result.
     */
    public static function decode($encoded)
    {
        // Trying to decode null, eh?
        if (is_null($encoded) || empty($encoded))
        {
            return null;
        }

        // Unpack into array of characters
        $chars = str_split($encoded);

        // Convert the character array to an array of bytes
        $bytes = array();
        foreach ($chars as $char)
        {
            $bytes[] = ord($char);
        }

        $val = self::recursiveDecode($bytes);

        if (!empty($bytes))
        {
            throw new CborException("There are more bytes than expected in the CBOR byte array.");
        }

        return $val;
    }

    /**
     * Does a decode from an array of bytes passed by reference.
     *
     * @param array $bytes Byte array.
     * @return mixed Decoded value.
     * @throws CborException If the byte array is not valid.
     */
    private static function recursiveDecode(&$bytes)
    {
        // Grab the first byte
        $first = array_shift($bytes);

        // Get the major type
        $major = $first & MajorType::BIT_MASK;
        $additional = $first & AdditionalType::BIT_MASK;

        switch($major)
        {
            case MajorType::POSITIVE_INT:
                return self::decodeIntValue($additional, $bytes);

            case MajorType::NEGATIVE_INT:
                $decoded = self::decodeIntValue($additional, $bytes);

                // Apply negative number logic
                $decoded += 1;
                $decoded *= -1;

                return $decoded;

            case MajorType::BYTE_STRING:
                return self::decodeByteString($additional, $bytes);

            case MajorType::UTF8_STRING:
                return self::decodeString($additional, $bytes);

            case MajorType::SEQUENCE:
                return self::decodeSequence($additional, $bytes);

            case MajorType::MAP:
                return self::decodeMap($additional, $bytes);

            case MajorType::SIMPLE_AND_FLOAT:
                return self::decodeSimple($additional, $bytes);

            default:
                return self::decodeTag($additional, $bytes);
        }
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

            // Must be 64 if it's not in any of the previous cases
            default:
                return self::encodeFirstByte($major_type, AdditionalType::UINT_64) . pack(PackFormat::UINT_64, $value >> 32, $value & 0xffffffff);
        }
    }

    /**
     * Decodes an integer value, based on the first byte and additional data.
     *
     * @param int $additional Additional type.
     * @param array $bytes Remaining bytes in string.
     * @throws CborException If the byte array is not long enough for the specified type of integer.
     * @return int Decoded integer.
     */
    private static function decodeIntValue($additional, &$bytes)
    {
        switch($additional)
        {
            case AdditionalType::UINT_8:
                $length = 1;
                break;

            case AdditionalType::UINT_16:
                $length = 2;
                break;

            case AdditionalType::UINT_32:
                $length = 4;
                break;

            case AdditionalType::UINT_64:
                $length = 8;
                break;

            default:
                return $additional;
        }

        self::checkByteArrayLength($bytes, $length);

        // Construct the value
        $value = 0;
        for ($i = $length-1; $i >= 0; $i--)
        {
            $value += array_shift($bytes) << ($i * 8);
        }

        return $value;
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
     * @return string The encoded byte string.
     */
    private static function encodeDouble($double)
    {
        // Make a 64 bit float out of the double
        $float = new Float64($double);

        // Can be exactly represented by a 16 bit float
        if (Float16::fits($float))
        {
            // Create the byte string
            return Float16::encode($float);
        }

        // Can be exactly represented by a 32 bit float, or a 32 bit subnormal
        else if (Float32::fits($float))
        {
            return Float32::encode($double);
        }

        // Must be a 64 bit float
        else
        {
            return Float64::encode($double);
        }
    }

    /**
     * Decodes a double value.
     *
     * @param int $length Size of the number, in bytes.
     * @param array $bytes The messages byte array.
     * @return float The decoded double.
     * @throws CborException If there aren't enough bytes in the remaining byte string.
     */
    private static function decodeDouble($length, &$bytes)
    {
        // Not enough bytes
        if ($length > count($bytes))
        {
            throw new CborException("The supplied byte string is too short to decode the specified double type.");
        }

        // Grab the required number of bytes
        $byteArray = array_splice($bytes, 0, $length);

        // Unpack a 32 bit double
        if ($length == 4)
        {
            return Float32::decode($byteArray);
        }

        // Unpack a 64 bit double
        else if ($length == 8)
        {
            return Float64::decode($byteArray);
        }

        // Unpack a 16 bit double
        else
        {
            return Float16::decode($byteArray);
        }
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
     * Decodes a CBOR UTF-8 string, based on the first byte and additional data.
     *
     * @param int $additional Additional type.
     * @param array $bytes Remaining bytes in string.
     * @throws CborException If the byte array is not long enough for the specified type of integer.
     * @return string UTF-8 encoded string.
     */
    private static function decodeString($additional, &$bytes)
    {
        $length = self::decodeIntValue($additional, $bytes);

        self::checkByteArrayLength($bytes, $length);

        $string = "";
        for ($i = 0; $i < $length; $i++)
        {
            $string .= chr(array_shift($bytes));
        }

        return $string;
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
     * Decodes a CBOR sequence, based on the first byte and additional data.
     *
     * @param int $additional Additional type.
     * @param array $bytes Remaining bytes in string.
     * @throws CborException If the byte array is not long enough for the specified type of integer.
     * @return array List of decoded  values.
     */
    private static function decodeSequence($additional, &$bytes)
    {
        $length = self::decodeIntValue($additional, $bytes);

        $sequence = array();
        for ($i = 0; $i < $length; $i++)
        {
            array_push($sequence, self::recursiveDecode($bytes));
        }

        return $sequence;
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
     * Decodes a CBOR map, based on the first byte and additional data.
     *
     * @param int $additional Additional type.
     * @param array $bytes Remaining bytes in string.
     * @throws CborException If keys in map are overwritten.
     * @return array Map of decoded values.
     */
    private static function decodeMap($additional, &$bytes)
    {
        $length = self::decodeIntValue($additional, $bytes);

        $map = array();
        for ($i = 0; $i < $length; $i++)
        {
            $key = self::recursiveDecode($bytes);
            if (array_key_exists($key, $map))
            {
                throw new CborException("Map contains multiple keys with same value.");
            }

            $value = self::recursiveDecode($bytes);
            $map[$key] = $value;
        }

        return $map;
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
     * Decodes a CBOR tag, based on the first byte and additional data.
     *
     * @param int $additional Additional type.
     * @param array $bytes Remaining bytes in string.
     * @throws CborException If the byte array is not long enough for the specified type of integer.
     * @return mixed Returns the CBOR value that the tag applies to.
     */
    private static function decodeTag($additional, &$bytes)
    {
        // Ignore tags for now and return the next value
        self::decodeIntValue($additional, $bytes);
        self::checkByteArrayLength($bytes, 1);
        return self::recursiveDecode($bytes);
    }

    /**
     * Decodes a CBOR byte string, based on the first byte and additional data.
     *
     * @param int $additional Additional type.
     * @param array $bytes Remaining bytes in string.
     * @throws CborException If the byte array is not long enough for the specified type of integer.
     * @return array Byte string.
     */
    private static function decodeByteString($additional, &$bytes)
    {
        $length = self::decodeIntValue($additional, $bytes);
        self::checkByteArrayLength($bytes, $length);

        $array = array();
        for ($i = 0; $i < $length; $i++)
        {
            array_push($array, array_shift($bytes));
        }

        return $array;
    }

    /**
     * Decodes a CBOR simple type, based on the first byte and additional data.
     *
     * @param int $additional Addition type.
     * @param array $bytes Remaining bytes in string.
     * @throws CborException If the byte array is not long enough for the specified type of integer.
     * @return mixed Decoded simple type.
     */
    private static function decodeSimple($additional, &$bytes)
    {
        switch($additional)
        {
            case AdditionalType::SIMPLE_FALSE:
                return false;

            case AdditionalType::SIMPLE_TRUE:
                return true;

            case AdditionalType::SIMPLE_UNDEFINED: // Deliberate fall-through
            case AdditionalType::SIMPLE_NULL:
                return null;

            case AdditionalType::FLOAT_16:
                return self::decodeDouble(2, $bytes);

            case AdditionalType::FLOAT_32:
                return self::decodeDouble(4, $bytes);

            case AdditionalType::FLOAT_64:
                return self::decodeDouble(8, $bytes);

            default:
                throw new CborException("$additional isn't a valid CBOR Additional Type for the Simple Major Type.");
        }
    }

    /**
     * Constructs the first byte of a CBOR data type, using the major type and additional information.
     *
     * @param int $major The major type to use.
     * @param int $additional The additional type to use.
     * @return string The encoded byte string.
     */
    public static function encodeFirstByte($major, $additional)
    {
        $first_byte = ($major & MajorType::BIT_MASK) | ($additional & AdditionalType::BIT_MASK);
        return pack(PackFormat::UINT_8, $first_byte);
    }

    /**
     * Check to see if the byte array is large enough for the number of values requested
     *
     * @param array $bytes An array of bytes.
     * @param int $length The number of bytes required.
     * @throws CborException If the byte array is too short.
     */
    private static function checkByteArrayLength(&$bytes, $length)
    {
        if ($length > count($bytes))
        {
            throw new CborException("CBOR byte stream abruptly ended.");
        }
    }

    /**
     * Converts an object to a map.
     *
     * @param $object Object to convert.
     * @return array The Map.
     */
    private static function objectToMap($object)
    {
        // If it's an object or an array, convert to a map
        if (is_array($object) || is_object($object))
        {
            $result = array();
            foreach ($object as $key => $value)
            {
                $result[$key] = self::objectToMap($value);
            }

            return $result;
        }

        // Just return the raw value
        return $object;
    }
}

?>
