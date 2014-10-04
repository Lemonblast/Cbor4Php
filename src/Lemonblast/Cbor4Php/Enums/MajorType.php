<?php namespace Lemonblast\Cbor4Php\Enums;

/**
 * Contains type definitions for the CBOR major type.
 *
 * Class MAJOR_TYPE
 * @package Lemonblast\Cbor4Php\Types
 */
abstract class MajorType
{
    const UNSIGNED_INT = 0b000000;
    const INT = 0b100000;
    const BYTE_STRING = 0b1000000;
    const UTF8_STRING = 0b1100000;
    const ARRAY_TYPE = 0b10000000; // This has a _TYPE on it because ARRAY is a php keyword
    const MAP = 0b10100000;
    const TAGS = 0b11000000;
    const SIMPLE_AND_FLOAT = 0b11100000;
}

?> 
