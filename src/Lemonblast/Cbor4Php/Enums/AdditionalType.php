<?php namespace Lemonblast\Cbor4Php\Enums;

/**
 * Contains CBOR additional types.
 *
 * Class AdditionalType
 * @package Lemonblast\Cbor4Php\Types
 */
class AdditionalType {

    const BIT_MASK = 0b00011111;
    const MAX_VALUE = 23;

    const SIMPLE_FALSE = 20;
    const SIMPLE_TRUE = 21;
    const SIMPLE_NULL = 22;
    const SIMPLE_UNDEFINED = 23;

    // Various sizes of uint
    const UINT_8 = 24;
    const UINT_16 = 25;
    const UINT_32 = 26;
    const UINT_64 = 27;

    const FLOAT16 = 25;
    const FLOAT32 = 26;
    const FLOAT64 = 27;
    const BREAK_TYPE = 31; // Break is a keyword in php, _TYPE appended
}

?> 
