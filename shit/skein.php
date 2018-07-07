<?php

// Least Significant Byte, 64-bit
function mergeLeast_64(array $input) {

    $length = sizeof($input);
    $output = [];

    for ($i = 0; $i < $length; $i += 8) {
        $ar = [
            __TOSIGNED32BIT(( ( $input[$i + 4] & 0xff ) << 0 ) |
                    ( ( $input[$i + 5] & 0xff ) << 8 ) |
                    ( ( $input[$i + 6] & 0xff ) << 16 ) |
                    ( ( $input[$i + 7] & 0xff ) << 24 )),
            ###
            __TOSIGNED32BIT(( ( $input[$i + 0] & 0xff ) << 0 ) |
                    ( ( $input[$i + 1] & 0xff ) << 8 ) |
                    ( ( $input[$i + 2] & 0xff ) << 16 ) |
                    ( ( $input[$i + 3] & 0xff ) << 24 ))
        ];
        array_push($output, $ar);
    }

    return $output;
}

function splitLeast_64(array $input) {


    $length = sizeof($input);
    $output = [];

    for ($i = 0; $i < $length; $i += 1) {
        $output[] = ( $input[$i][1] >> 0 ) & 0xff;
        $output[] = ( $input[$i][1] >> 8 ) & 0xff;
        $output[] = ( $input[$i][1] >> 16 ) & 0xff;
        $output[] = ( $input[$i][1] >> 24 ) & 0xff;
        $output[] = ( $input[$i][0] >> 0 ) & 0xff;
        $output[] = ( $input[$i][0] >> 8 ) & 0xff;
        $output[] = ( $input[$i][0] >> 16 ) & 0xff;
        $output[] = ( $input[$i][0] >> 24 ) & 0xff;
    }

    return $output;
}

function __TOSIGNED32BIT($x) {

    if (PHP_INT_SIZE === 8) {

        #if ($x > 0x7FFFFFFF) {
        #$x -= 0x100000000;
        #}
        $o = pack('l', $x);
        $d = unpack('l', $o);
        return $d[1];
    }

    return $x;
}

function ulong(array $x) {
    return array(__TOSIGNED32BIT($x[0])
        , __TOSIGNED32BIT($x[1]));
}

/* function __and($x, $y) {

  return [$x[0] & $y[0], $x[1] & $y[1]];
  } */

function __or($x, $y) {
    return [__TOSIGNED32BIT(__TOSIGNED32BIT($x[0]) | __TOSIGNED32BIT($y[0]))
        , __TOSIGNED32BIT(__TOSIGNED32BIT($x[1]) | __TOSIGNED32BIT($y[1]))];
}

function __xor($x, $y) {
    return [__TOSIGNED32BIT(__TOSIGNED32BIT($x[0]) ^ __TOSIGNED32BIT($y[0]))
        , __TOSIGNED32BIT(__TOSIGNED32BIT($x[1]) ^ __TOSIGNED32BIT($y[1]))];
}

function lt_32($x, $y) {
    $a = __TOSIGNED32BIT(__TOSIGNED32BIT($x >> 16) & 0xffff);
    $b = __TOSIGNED32BIT(__TOSIGNED32BIT($y >> 16) & 0xffff);

    return ( $a < $b ) || ( ( $a === $b ) && ( __TOSIGNED32BIT($x & 0xffff) < __TOSIGNED32BIT($y & 0xffff) ) );
}

function __add($x, $y) {
    $b = ( __TOSIGNED32BIT($x[1])) + ( __TOSIGNED32BIT($y[1]) );
    $a = __TOSIGNED32BIT($x[0]) + __TOSIGNED32BIT($y[0]) + ( lt_32($b, $x[1]) ? 0x1 : 0x0 );

    return [__TOSIGNED32BIT($a), __TOSIGNED32BIT($b)];
}

function shl($x, $n) {
    $a = __TOSIGNED32BIT($x[0]);
    $b = __TOSIGNED32BIT($x[1]);

    if ($n >= 32) {
        return [__TOSIGNED32BIT($b << ( $n - 32 )), 0x0];
    } else {
        return [__TOSIGNED32BIT(__TOSIGNED32BIT($a << $n) | __TOSIGNED32BIT($b >> ( 32 - $n )))
            , __TOSIGNED32BIT($b << $n)];
    }
}

function shr($x, $n) {
    $a = __TOSIGNED32BIT($x[0]);
    $b = __TOSIGNED32BIT($x[1]);

    if ($n >= 32) {
        return [0x0, __TOSIGNED32BIT($a >> ( $n - 32 ))];
    } else {
        return [__TOSIGNED32BIT($a >> $n)
            , __TOSIGNED32BIT(( $a << ( 32 - $n ) ) | __TOSIGNED32BIT($b >> $n))];
    }
}

function rotl($x, $n) {
    return __or(shr(__TOSIGNED32BIT($x), ( 64 - $n)), shl($x, $n));
}

function __crop($size, array $hash, $righty = FALSE) {
    $length = floor(( $size + 7 ) / 8);
    $remain = $size % 8;

    if ($righty)
        $hash = array_slice($hash, count($hash) - $length);
    else
        $hash = array_slice($hash, 0, $length);


    if ($remain > 0)
        $hash[$length - 1] &= __TOSIGNED32BIT(0xff << ( 8 - $remain )) & 0xff;


    return $hash;
}

#  var merge = mergeLeast_64,
#     split = splitLeast_64,

$PARITY = [0x1BD11BDA, 0xA9FC1A22];

$TWEAK = array(
    'KEY' => 0x00,
    'CONFIG' => 0x04,
    'PERSONALIZE' => 0x08,
    'PUBLICKEY' => 0x10,
    'NONCE' => 0x14,
    'MESSAGE' => 0x30,
    'OUT' => 0x3F
);

$VARS = array(
    256 => array(
        'bytes' => 32,
        'words' => 4,
        'rounds' => 72,
        'permute' => [0, 3, 2, 1],
        'rotate' => array(
            [14, 16],
            [52, 57],
            [23, 40],
            [5, 37],
            [25, 33],
            [46, 12],
            [58, 22],
            [32, 32]
        )
    )
);

function tweaker($pos, $type, $first, $finish) {

    $first = intval($first);
    $finish = intval($finish);

    $a = __TOSIGNED32BIT($pos);
    $b = __TOSIGNED32BIT($pos / pow(2, 32));
    $c = __TOSIGNED32BIT($pos / pow(2, 64));

    $d = __TOSIGNED32BIT(__TOSIGNED32BIT(( $finish ? 0x80 : 0 ) | ( $first ? 0x40 : 0 ) | $type) << 24);

    return splitLeast_64([[$b, $a], [$d, $c]]);
}

function mix0($x, $y) {
    return __add($x, $y);
}

function mix1($x, $y, $r) {
    return __xor(rotl($y, $r), $x);
}

function threefish(array $key, array $tweak, array $plain, array $vars) {
    global $PARITY;
    $i = 0;
    $j = 0;
    $r = 0;
    $s = 0;
    $mixer = NULL;
    $sched = NULL;
    $chain = NULL;
    $words = +$vars ['words'];
    $rounds = +$vars ['rounds'];
    $rotate = $vars['rotate'];
    $permute = $vars['permute'];

    $key = mergeLeast_64($key);
    $tweak = mergeLeast_64($tweak);
    $plain = mergeLeast_64($plain);

    $key[$words] = ulong($PARITY);

    for ($i = 0; $i < $words; ++$i)
        $key[$words] = __xor($key[$words], $key[$i]);

    $tweak[2] = __xor($tweak[0], $tweak[1]);
    var_dump($tweak);
    for ($r = 0, $s = 0; $r < $rounds; ++$r) {
        $mixer = array_slice($plain, 0); //just copy, no ref

        if (0 === ( $r % 4 )) {
            $sched = array(); //max is $words

            for ($i = 0; $i <= $words; ++$i)
                $sched[$i] = $key[($s + $i) % ($words + 1)];

            $sched[$words - 3] = __add($sched[$words - 3], $tweak[$s % 3]);
            $sched[$words - 2] = __add($sched[$words - 2], $tweak[($s + 1) % 3]);
            $sched[$words - 1] = __add($sched[$words - 1], [0, $s]);

            for ($i = 0; $i < $words; ++$i)
                $mixer[$i] = __add($mixer[$i], $sched[$i]);

            ++$s;
        }

        for ($i = 0; $i < ( $words / 2 ); $i++) {
            $j = 2 * $i;
            $mixer[$j + 0] = mix0($mixer[$j + 0], $mixer[$j + 1]);
            $mixer[$j + 1] = mix1($mixer[$j + 0], $mixer[$j + 1], $rotate[$r % 8][$i]);
        }

        for ($i = 0; $i < $words; $i++) {
            $plain[$i] = $mixer[$permute[$i]];
        }
    }

    for ($chain = [], $i = 0; $i < $words; $i++)
        $chain[$i] = __add($plain[$i], $key[($s + $i) % ($words + 1)]);

    $chain[$words - 3] = __add($chain[$words - 3], $tweak[$s % 3]);
    $chain[$words - 2] = __add($chain[$words - 2], $tweak[($s + 1) % 3]);
    $chain[$words - 1] = __add($chain[$words - 1], [0, $s]);

    return splitLeast_64($chain);
}

function ubi($chain, array $message, $type, array $vars) {

    $bytes = $vars['bytes'];
    $count = sizeof($message);
    $blocks = [];

    #$message.length += count == 0 ? bytes :
    # bytes - ( ( count % bytes ) || bytes );
    $cc = ($count % $bytes);
    $len = $count == 0 ? $bytes : $bytes - ($cc ? $cc : $bytes);

    $message = array_pad($message, count($message) + $len, 0);

    while (count($message) > 0) {
        $blocks[] = array_slice($message, 0, $bytes);
        $message = array_slice($message, $bytes);
    }

    for ($k = 0, $l = count($blocks); $k < $l; $k++) {
        $pos = $bytes * ( $k + 1 );
        $first = $k === 0;
        $finish = $k === ( $l - 1 );

        $tweak = tweaker(min($count, $pos), $type, $first, $finish);
        $chain = threefish($chain, $tweak, $blocks[$k], $vars);


        for ($i = 0; $i < count($chain); $i++)
            $chain[$i] ^= $blocks[$k][$i];
    }

    return array_slice($chain, 0, $bytes);
}

function skein($digest, array $data) {
    global $VARS, $TWEAK;
    #$config;
    #$chain;
    $out = [0, 0, 0, 0, 0, 0, 0, 0];
    #$output = $size ? $size : $digest;
    $output = $digest;
    $vars = $VARS[$digest];
    $bytes = $vars['bytes'];

    $chain = array_fill(0, $bytes, 0);

    $config = [];
    array_push($config, 0x53, 0x48, 0x41, 0x33); // Schema: "SHA3"
    array_push($config, 0x01, 0x00, 0x00, 0x00); // Version / Reserved
    $config = array_merge($config, splitLeast_64([[0, $output]]));
    $config = array_pad($config, 32, NULL);



    #if ($key)
    #    $chain = ubi($chain, $key, $TWEAK['KEY'], $vars);

    $chain = ubi($chain, $config, $TWEAK['CONFIG'], $vars);

    $chain = ubi($chain, $data, $TWEAK['MESSAGE'], $vars);

    return ubi($chain, $out, $TWEAK['OUT'], $vars);
}

function skein256(array $data, array $key = array()) {
    $xx = skein(256, $data);
    var_dump($xx);
    die;
    return __crop(256, $xx, FALSE);
}

$vects = array(
    array(
        'c8877087da56e072870daa843f176e9453115929094c3a40c463a196c29bf7ba',
        "test"
    ),
);

#var_dump(bin2hex(implode('', array_map('chr', array(65,129,71,92,176,194,45,88,174,132,126,54,142,145,180,102,158,162,216,75,205,85,219,240,31,226,75,174,101,113,221,8)))));die;
#4181475cb0c22d58ae847e368e91b4669ea2d84bcd55dbf01fe24bae6571dd08
# Blyead
/* 7 should be.... 65,129,71,92,176,194,45,88,174,132,126,54,142,145,180,102,
 * 158,162,216,75,205,85,219,240,31,226,75,174,101,113,221,8

 * 
 * 
 * string(114) "148,222,123,45,188,9,220,147,226,109,50,6,151,22,
 * 217,31,214,67,134,72,239,104,80,168,178,194,46,243,56,212,113,248"

 * 
 *  */

$o = skein256(array_map('ord', str_split('test')));
$h = bin2hex(implode('', array_map('chr', $o)));
var_dump($h);
die;

foreach ($vects as $i => $v) {
    list($hash_x, $data) = $v;

    #$o = skein256(strlen($data), array_map('ord', str_split($data)));
    $h = bin2hex(implode('', array_map('chr', $o)));

    if ($h === $hash_x) {
        printf("TEST OK\n");
    } else {
        printf("$i test failed!!!\n");
        var_dump($h, implode(',', $o));
    }
    #die;
}