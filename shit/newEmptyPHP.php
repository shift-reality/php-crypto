<?php

require_once 'u64.php';
require_once 'grojs.php';
$input = array_map('ord', str_split('abcdbcdecdefdefgefghfghighijhijkijkljklmklmnlmnomnopnopq'));
$vv = array_slice(GRO($input), 0, 16);
$out8 = int32Buffer2Bytes($vv);
var_dump(bin2hex(implode('', array_map('chr', $out8))));
