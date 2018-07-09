<?php

use Shift196\AKashLib\Util\UInt64String as i;

require_once 'vendor/autoload.php';

$str0 = hex2bin('1BD11BDA' . 'A9FC1A22');
$str1 = hex2bin('10203040' . '50607080');

var_dump(bin2hex(i::___shiftRight($str0, 32)));
