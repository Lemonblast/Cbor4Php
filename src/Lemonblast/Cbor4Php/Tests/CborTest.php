<?php namespace Lemonblast\Cbor4Php\Test;

use Lemonblast\Cbor4Php\Cbor;

class CborTest extends \PHPUnit_Framework_TestCase
{
    function testEncodeUINT5()
    {
        $encoded = Cbor::encode(10);

        // Should be a byte string of just 10
        $this->assertEquals(pack('C', 10), $encoded);
    }

    function testEncodeUINT8()
    {
        $encoded = Cbor::encode(255);

        // Should be a byte string of 24, 140
        $this->assertEquals(pack('C', 24) . pack('C', 255), $encoded);
    }

    function testEncodeUINT16()
    {
        $encoded = Cbor::encode(65535);

        // Should be a byte string of 25, 140
        $this->assertEquals(pack('C', 25) . pack('n', 65535), $encoded);
    }

    function testEncodeUINT32()
    {
        $encoded = Cbor::encode(4294967295);

        // Should be a byte string of 26, 4294967295
        $this->assertEquals(pack('C', 26) . pack('N', 4294967295), $encoded);
    }

    function testEncodeUINT64()
    {
        $encoded = Cbor::encode(18446744073709551615);

        $first = 18446744073709551615 >> 32;
        $second = 18446744073709551615 & 0xffffffff;

        // Should be a byte string of 27, 18446744073709551615
        $this->assertEquals(pack('C', 27) . pack('NN', $first, $second), $encoded);
    }

    function testEncodeSuperLargeInt()
    {
        $this->setExpectedException("Lemonblast\\Cbor4Php\\CborException", "The input integer is too large to be encoded in CBOR.");

        Cbor::encode(99999999999999999999);
    }

    function testEncodeSignedInt()
    {
        $encoded = Cbor::encode(-500);

        $this->assertEquals(pack('C', 0b00111001) . pack('n', 499), $encoded);
    }

    function testEncodeBoolTrue()
    {
        $encoded = Cbor::encode(true);

        $this->assertEquals(pack('C', 0b11110101), $encoded);
    }

    function testEncodeBoolFalse()
    {
        $encoded = Cbor::encode(false);

        $this->assertEquals(pack('C', 0b11110100), $encoded);
    }

    function testEncodeNull()
    {
        $encoded = Cbor::encode(null);

        $this->assertEquals(pack('C', 0b11110110), $encoded);
    }

    function testEncodeUnknown()
    {
        /// XXX: This needs to be replaced with a vfs file so that there is no platform dependence
        $f = fopen("/dev/null", "r");
        fclose($f);
        $encoded = Cbor::encode($f);

        $this->assertEquals(pack('C', 0b11110111), $encoded);
    }

    function testDecode()
    {

    }
}

?>
