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
        $this->setExpectedException("Lemonblast\\Cbor4Php\\CborException", "Value is too large to be encoded in CBOR.");

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
        // Open and close a resource, only way to get an unknown type in PHP.
        $f = curl_init('localhost');
        curl_close($f);

        $encoded = Cbor::encode($f);

        $this->assertEquals(pack('C', 0b11110111), $encoded);
    }

    function testStringEncodeEmpty()
    {
        $encoded = Cbor::encode("");

        $this->assertEquals(pack('C', 0b01100000), $encoded);
    }

    function testStringEncodeCharacter()
    {
        $encoded = Cbor::encode("a");

        $this->assertEquals(pack('CC', 0b1100001, 97), $encoded);
    }

    function testStringEncodeLowerAlphabet()
    {
        $encoded = Cbor::encode("abcdefghijklmnopqrstuvwxyz");

        $this->assertEquals(pack('CCCCCCCCCCCCCCCCCCCCCCCCCCCC', 0b01111000, 0b11010, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122), $encoded);
    }

    function testStringEncodeUpperAlphabet()
    {
        $encoded = Cbor::encode("ABCDEFGHIJKLMNOPQRSTUVWXYZ");

        $this->assertEquals(pack('CCCCCCCCCCCCCCCCCCCCCCCCCCCC', 0b01111000, 0b11010, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90), $encoded);
    }

    function testEncodeIntSequence()
    {
        $encoded = Cbor::encode(array(1,2,3));

        $this->assertEquals(pack('CCCC', 0b10000011, 0b00000001, 0b00000010, 0b00000011), $encoded);
    }

    function testEncodeIntMap()
    {
        $encoded = Cbor::encode(array(1 => 1, 2 => 2, 3 => 3));

        $this->assertEquals(pack('CCCCCCC', 0b10100011, 0b00000001, 0b00000001, 0b00000010, 0b00000010, 0b00000011, 0b00000011), $encoded);
    }

    function testEncodeDouble_32()
    {
        $encoded = Cbor::encode(1.5);

        $this->assertEquals(pack('CCCCC', 0xfa, 0x3f, 0xc0, 0x00, 0x00), $encoded);
    }

    function testEncodeDouble_64()
    {
        Cbor::$ENCODE_DOUBLE_64_BIT = true;
        $encoded = Cbor::encode(1.5);

        $this->assertEquals(pack('CCCCCCCCC', 0xfb, 0x3f, 0xf8, 0x00, 0x00, 0x00, 0x00, 0x00,0x00), $encoded);
    }

    function testDecode()
    {

    }
}

?>
