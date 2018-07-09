<?php

namespace Shift196\AKashLib\Util;

final
    class UInt64String
{

    public static
        function ___pack($hi, $lo)
    {
        return pack('NN', $hi, $lo);
    }

    public static
        function ___unpack($word)
    {
        return array_values(unpack('N*', $word));
    }

    public static
        function ___xor($a0, $a1)
    {
        return $a0 ^ $a1;
    }

    public static
        function ___or($a0, $a1)
    {
        return $a0 | $a1;
    }

    public static
        function ___and($a0, $a1)
    {
        return $a0 & $a1;
    }

    public static
        function ___not($a)
    {
        return ~$a;
    }

    public static
        function ___shiftRight($word, $bits)
    {

        if ($bits === 0)
            return $word;

        list($wordHi, $wordLo) = static::___unpack($word);

        $bits %= 64;
        $m    = -1 << (64 - $bits);

        if ($bits >= 32)
            return static::___pack(-1, ($wordHi >> ($bits - 32)) | $m);
        else
        {

            $n = ~$m;

            return static::___pack(
                    ($wordHi >> $bits) | (-1 << (32 - $bits))
                    ,
                    //
                    (($wordLo >> $bits & $n) |
                    ($wordHi << (32 - $bits))) & $n);
        }
    }

}
