<?php namespace Lemonblast\Cbor4Php\Test;

use Lemonblast\Cbor4Php\Cbor;

class CborTest extends \PHPUnit_Framework_TestCase
{
    function testEncodeUINT5()
    {
        $encoded = Cbor::encode(10);

        //Should be a byte string of just 10
        $this->assertEquals(pack('c', 10), $encoded);
    }

    function testEncodeUINT8()
    {
        $encoded = Cbor::encode(255);

        //Should be a byte string of 24, 140
        $this->assertEquals(pack('C', 24) . pack('C', 255), $encoded);
    }

    function testEncodeUINT16()
    {
        $encoded = Cbor::encode(65535);

        //Should be a byte string of 25, 140
        $this->assertEquals(pack('C', 25) . pack('n', 65535), $encoded);
    }

    function testEncodeUINT32()
    {
        $encoded = Cbor::encode(4294967295);

        //Should be a byte string of 26, 4294967295
        $this->assertEquals(pack('C', 26) . pack('N', 4294967295), $encoded);
    }

    function testEncodeUINT64()
    {
        $encoded = Cbor::encode(18446744073709551615);

        $first = 18446744073709551615 >> 32;
        $second = 18446744073709551615 & 0x00000000ffffffff;

        //Should be a byte string of 27, 18446744073709551615
        $this->assertEquals(pack('C', 27) . pack('NN', $first, $second), $encoded);
    }

    function testEncodeSuperLargeInt()
    {
        $this->setExpectedException("Lemonblast\\Cbor4Php\\CborException", "The input integer is too large to be encoded in CBOR.");

        Cbor::encode(99999999999999999999);
    }

    function testDecode()
    {

    }
}

?>
