<?php namespace Lemonblast\Cbor4Php\Enums;


class PackFormat
{
    const UINT_8 = "C";
    const UINT_16 = "n";
    const UINT_32 = "N";

    // Split into 2 32 bit packs
    const UINT_64 = "NN";
}

?> 