<?php namespace Lemonblast\Cbor4Php\Enums;

/**
 * Contains type definitions for the CBOR major type.
 *
 * Class MAJOR_TYPE
 * @package Lemonblast\Cbor4Php\Types
 */
abstract class MajorType {
    const BIT_MASK = 0b11100000;

    const POSITIVE_INT = 0b000000;
    const NEGATIVE_INT = 0b100000;
    const BYTE_STRING = 0b1000000;
    const UTF8_STRING = 0b1100000;
    const SEQUENCE = 0b10000000;
    const MAP = 0b10100000;
    const TAG = 0b11000000;
    const SIMPLE_AND_FLOAT = 0b11100000;
}

?> 
