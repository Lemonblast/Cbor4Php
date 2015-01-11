<?php namespace Lemonblast\Cbor4Php\Test;

use Lemonblast\Cbor4Php\Cbor;

const CBOR_EXCEPTION = 'Lemonblast\Cbor4Php\CborException';

class CborTest extends \PHPUnit_Framework_TestCase
{
    function testEncodeUINT5()
    {
        $encoded = Cbor::encode(10);

        // Should be a byte string of just 10
        $this->assertEquals(pack('C', 10), $encoded);
    }

    function testDecodeUINT5()
    {
        $decoded = Cbor::decode(pack('C', 10));

        $this->assertEquals(10, $decoded);
    }

    function testEncodeUINT8()
    {
        $encoded = Cbor::encode(255);

        // Should be a byte string of 24, 140
        $this->assertEquals(pack('C', 24) . pack('C', 255), $encoded);
    }

    function testDecodeUINT8()
    {
        $decoded = Cbor::decode(pack('C', 24) . pack('C', 255));

        $this->assertEquals(255, $decoded);
    }

    function testEncodeUINT16()
    {
        $encoded = Cbor::encode(65535);

        // Should be a byte string of 25, 140
        $this->assertEquals(pack('C', 25) . pack('n', 65535), $encoded);
    }

    function testDecodeUINT16()
    {
        $decoded = Cbor::decode(pack('C', 25) . pack('n', 65535));

        $this->assertEquals(65535, $decoded);
    }

    function testEncodeUINT32()
    {
        // Max value in a 32 bit int (signed)
        $encoded = Cbor::encode(2147483647);

        // Should be a byte string of 26, 2147483647
        $this->assertEquals(pack('C', 26) . pack('N', 2147483647), $encoded);
    }

    function testDecodeUINT32()
    {
        $decoded = Cbor::decode(pack('C', 26) . pack('N', 2147483647));

        $this->assertEquals(2147483647, $decoded);
    }

    function testEncodeUINT64()
    {
        // Max value in a 64 bit int (signed)
        $encoded = Cbor::encode(9223372036854775807);

        $first = 9223372036854775807 >> 32;
        $second = 9223372036854775807 & 0xffffffff;

        // Should be a byte string of 27, 9223372036854775807
        $this->assertEquals(pack('C', 27) . pack('NN', $first, $second), $encoded);
    }

    function testEncodeSignedInt()
    {
        $encoded = Cbor::encode(-500);

        $this->assertEquals(pack('C', 0b00111001) . pack('n', 499), $encoded);
    }

    function testDecodeSignedInt()
    {
        $decoded = Cbor::decode(pack('C', 0b00111001) . pack('n', 499));

        $this->assertEquals(-500, $decoded);
    }

    function testEncodeBoolTrue()
    {
        $encoded = Cbor::encode(true);

        $this->assertEquals(pack('C', 0b11110101), $encoded);
    }

    function testDecodeBoolTrue()
    {
        $decoded = Cbor::decode(pack('C', 0b11110101));

        $this->assertEquals(true, $decoded);
    }

    function testEncodeBoolFalse()
    {
        $encoded = Cbor::encode(false);

        $this->assertEquals(pack('C', 0b11110100), $encoded);
    }

    function testDecodeBoolFalse()
    {
        $decoded = Cbor::decode(pack('C', 0b11110100));

        $this->assertEquals(false, $decoded);
    }

    function testEncodeNull()
    {
        $encoded = Cbor::encode(null);

        $this->assertEquals(pack('C', 0b11110110), $encoded);
    }

    function testDecodeNull()
    {
        $decoded = Cbor::decode(pack('C', 0b11110110));

        $this->assertEquals(null, $decoded);
    }

    function testEncodeUnknown()
    {
        // Open and close a resource (easy way to get an unknown type in PHP)
        $sock = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_close($sock);

        $encoded = Cbor::encode($sock);

        $this->assertEquals(pack('C', 0b11110111), $encoded);
    }

    function testDecodeUnknown()
    {
        $decoded = Cbor::decode(pack('C', 0b11110111));

        $this->assertEquals(null, $decoded);
    }

    function testEncodeStringEmpty()
    {
        $encoded = Cbor::encode("");

        $this->assertEquals(pack('C', 0b01100000), $encoded);
    }

    function testEncodeStringCharacter()
    {
        $encoded = Cbor::encode("a");

        $this->assertEquals(pack('C*', 0b1100001, 97), $encoded);
    }

    function testEncodeStringLowerAlphabet()
    {
        $encoded = Cbor::encode("abcdefghijklmnopqrstuvwxyz");

        $this->assertEquals(pack('C*', 0b01111000, 0b11010, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122), $encoded);
    }

    function testEncodeStringUpperAlphabet()
    {
        $encoded = Cbor::encode("ABCDEFGHIJKLMNOPQRSTUVWXYZ");

        $this->assertEquals(pack('C*', 0b01111000, 0b11010, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90), $encoded);
    }

    function testDecodeStringCharacter()
    {
        $decoded = Cbor::decode(pack('C*', 0b1100001, 97));

        $this->assertEquals("a", $decoded);
    }

    function testDecodeStringLowerAlphabet()
    {
        $decoded = Cbor::decode(pack('C*', 0b01111000, 0b11010, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122));

        $this->assertEquals("abcdefghijklmnopqrstuvwxyz", $decoded);
    }

    function testDecodeStringUpperAlphabet()
    {
        $decoded = Cbor::decode(pack('C*', 0b01111000, 0b11010, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90));

        $this->assertEquals("ABCDEFGHIJKLMNOPQRSTUVWXYZ", $decoded);
    }

    function testEncodeIntSequence()
    {
        $encoded = Cbor::encode(array(1,2,3));

        $this->assertEquals(pack('C*', 0b10000011, 0b00000001, 0b00000010, 0b00000011), $encoded);
    }

    function testDecodeIntSequence()
    {
        $decoded = Cbor::decode(pack('C*', 0b10000011, 0b00000001, 0b00000010, 0b00000011));

        $this->assertEquals(array(1,2,3), $decoded);
    }

    function testEncodeIntMap()
    {
        $encoded = Cbor::encode(array(1 => 1, 2 => 2, 3 => 3));

        $this->assertEquals(pack('C*', 0b10100011, 0b00000001, 0b00000001, 0b00000010, 0b00000010, 0b00000011, 0b00000011), $encoded);
    }

    function testDecodeIntMap()
    {
        $decoded = Cbor::decode(pack('C*', 0b10100011, 0b00000001, 0b00000001, 0b00000010, 0b00000010, 0b00000011, 0b00000011));

        $this->assertEquals(array(1 => 1, 2 => 2, 3 => 3), $decoded);
    }

    function testEncodeFloat_16()
    {
        $encoded1 = Cbor::encode(0.015625);
        $encoded2 = Cbor::encode(1.5);
        $encoded3 = Cbor::encode(0.5);
        $encoded4 = Cbor::encode(-0.5);
        $encoded5 = Cbor::encode(0.0);
        $encoded6 = Cbor::encode(INF);
        $encoded7 = Cbor::encode(-INF);
        $encoded8 = Cbor::encode(NAN);

        $this->assertEquals(pack('C*',0xf9, 0x24, 0x00), $encoded1);
        $this->assertEquals(pack('C*',0xf9, 0x3e, 0x00), $encoded2);
        $this->assertEquals(pack('C*',0xf9, 0x38, 0x00), $encoded3);
        $this->assertEquals(pack('C*',0xf9, 0xb8, 0x00), $encoded4);
        $this->assertEquals(pack('C*',0xf9, 0x00, 0x00), $encoded5);
        $this->assertEquals(pack('C*',0xf9, 0x7c, 0x00), $encoded6);
        $this->assertEquals(pack('C*',0xf9, 0xfc, 0x00), $encoded7);
        $this->assertEquals(pack('C*',0xf9, 0x7e, 0x00), $encoded8);
    }

    function testDecodeFloat_16()
    {
        $decoded1 = Cbor::decode(pack('C*',0xf9, 0x24, 0x00));
        $decoded2 = Cbor::decode(pack('C*',0xf9, 0x3e, 0x00));
        $decoded3 = Cbor::decode(pack('C*',0xf9, 0x38, 0x00));
        $decoded4 = Cbor::decode(pack('C*',0xf9, 0xb8, 0x00));

        $decoded5 = Cbor::decode(pack('C*',0xf9, 0x00, 0x00));
        $decoded6 = Cbor::decode(pack('C*',0xf9, 0x80, 0x00)); // -0.0
        $decoded7 = Cbor::decode(pack('C*',0xf9, 0x7c, 0x00));
        $decoded8 = Cbor::decode(pack('C*',0xf9, 0xfc, 0x00));
        $decoded9 = Cbor::decode(pack('C*',0xf9, 0x7e, 0x00));

        $subnormal = Cbor::decode(pack('C*',0xf9, 0x00, 0x01));

        $this->assertEquals(0.015625, $decoded1);
        $this->assertEquals(1.5, $decoded2);
        $this->assertEquals(0.5, $decoded3);
        $this->assertEquals(-0.5, $decoded4);

        $this->assertEquals(0.0, $decoded5);
        $this->assertEquals(0.0, $decoded6);
        $this->assertEquals(INF, $decoded7);
        $this->assertEquals(-INF, $decoded8);
        $this->assertTrue(is_nan($decoded9));

        $this->assertEquals(pow(2, -24), $subnormal);
    }

    function testEncodeDouble_32()
    {
        $encoded = Cbor::encode(100000.0);

        $this->assertEquals(pack('C*', 0xfa, 0x47, 0xc3, 0x50, 0x00), $encoded);
    }

    function testDecodeDouble_32()
    {
        $decoded = Cbor::decode(pack('C*', 0xfa, 0x3f, 0xc0, 0x00, 0x00));

        $this->assertEquals(1.5, $decoded);
    }

    function testEncodeDouble_64()
    {
        $encoded = Cbor::encode(1.7);
        $subnormal = Cbor::encode(pow(2, -1 * 1074));

        $this->assertEquals(pack('C*', 0xfb, 0x3f, 0xfb, 0x33, 0x33, 0x33, 0x33, 0x33, 0x33), $encoded);
        $this->assertEquals(pack('C*', 0xfb, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x01), $subnormal);
    }

    function testDecodeDouble_64()
    {
        $decoded = Cbor::decode(pack('C*', 0xfb, 0x3f, 0xf8, 0x00, 0x00, 0x00, 0x00, 0x00,0x00));

        $this->assertEquals(1.5, $decoded);
    }

    function testDecodeByteString()
    {
        $decoded = Cbor::decode(pack('C*', 0b01000001, 0xff));

        $this->assertEquals(array(0xff), $decoded);
    }

    function testEncodeResource()
    {
        $sock = socket_create(AF_INET, SOCK_STREAM, 0);

        $encoded = Cbor::encode($sock);

        $this->assertEquals(null, $encoded);
    }

    function testEncodeObject()
    {
        // Make a nested array
        $nArray = array('NKey' => 'NVal');
        $array = array('Key1' => 'Val1', 'Key2' => $nArray);

        // Make a nested object out of the array
        $nObject = (object) $nArray;
        $object = (object) $array;
        $object->Key2 = $nObject;

        // Encode the object and the map
        $encodedObject = Cbor::encode($object);
        $encodedArray = Cbor::encode($array);

        $this->assertEquals($encodedArray, $encodedObject);
    }

    function testDecodePHPNull()
    {
        $decoded = Cbor::decode(null);

        $this->assertEquals(null, $decoded);
    }

    function testDecodeTag()
    {
        // Test proper value tagging
        $decoded = Cbor::decode(pack('C*', 0b10000011, 0b11000011, 0b00000001, 0b00000010, 0b00000011));

        $this->assertEquals(array(1,2,3), $decoded);

        // Test if only tags are passed and no real value after
        $this->setExpectedException(CBOR_EXCEPTION);

        Cbor::decode(pack('C*', 0b11000011));
    }

    function testDecodeWithExtraByte()
    {
        $this->setExpectedException(CBOR_EXCEPTION);

        // Decode 255 with an extra byte
        Cbor::decode(pack('C', 24) . pack('C', 255) . pack('C', 100));
    }

    function testDecodeDoubleWithMissingBytes()
    {
        $this->setExpectedException(CBOR_EXCEPTION);

        Cbor::decode(pack('C*', 0xfb, 0x3f, 0xf8, 0x00, 0x00, 0x00, 0x00, 0x00));
    }

    function testDecodeMapDuplicateKey()
    {
        $this->setExpectedException(CBOR_EXCEPTION);

        Cbor::decode(pack('C*', 0b10100011, 0b00000001, 0b00000001, 0b00000001, 0b00000010, 0b00000011, 0b00000011));
    }

    function testDecodeUnknownSimple()
    {
        $this->setExpectedException(CBOR_EXCEPTION);

        Cbor::decode(pack('C', 0b11110011));
    }

    function testAbruptStringEnd()
    {
        $this->setExpectedException(CBOR_EXCEPTION);

        Cbor::decode(pack('C*', 0b01111011, 97, 98));
    }
}

?>
