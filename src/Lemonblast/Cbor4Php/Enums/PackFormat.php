<?php namespace Lemonblast\Cbor4Php\Enums;

/**
 * Contains pack formats for different data types.
 *
 * Class PackFormat
 * @package Lemonblast\Cbor4Php\Enums
 */
class PackFormat {

    const UNIT_8_SET = "C*";
    const UINT_8 = "C";
    const UINT_16 = "n";
    const UINT_32 = "N";

    // Split into 2 32 bit packs
    const UINT_64 = "NN";

    const FLOAT_32 = "f";
    const FLOAT_64 = "d";
}

?> 
