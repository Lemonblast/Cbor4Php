# Cbor4Php

[CBOR](http://cbor.io/) (Concise Binary Object Representation) is a tiny data format that can be used in place of JSON. Cbor4Php is a simple CBOR encoder/decoder for PHP.

## Installation
To install the library through composer, you simply need to add the following to `composer.json` and run `composer update`:

```JavaScript
{
    "require": {
       "lemonblast/cbor4php": "dev-master"
    }
}
```
Once installed, you can use the Cbor class (`Lemonblast\Cbor4Php\Cbor`) to encode and decode CBOR data.

## Usage
Include the Cbor4Php library in your source:

```PHP
use Lemonblast\Cbor4Php\Cbor;
```

### Encoding
To encode a variable into a CBOR byte string call the encode method and pass the value as a parameter:

```PHP
$foo = Cbor::encode($bar);
```

Encoding an object will convert it to an associative array, and encode it as such. Only public fields will be encoded.

Encoding a PHP resource is not supported and will result in a null return value.

### Decoding
To decode a CBOR byte string into a PHP variable:

```PHP
$bar = Cbor::decode($foo);
```

CBOR data tags are ignored during the decode process.
Decoding a null value or empty string will result in a null return value.
