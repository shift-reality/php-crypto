<?php

namespace Shift196\AKashLib\Builtin;

use Shift196\AKashLib\IHashFunction;
use Shift196\AKashLib\Util\UnsignedInt64 as o_u64;

final
    class Keccak
    implements IHashFunction
{

    const
        HASH_DATA_AREA = 136;

    const
        KECCAK_ROUNDS = 24;

    /**
     *
     * @var Keccak
     */
    private static
        $_instance;

    private
        $keccakf_rndc, $keccakf_rotc, $keccakf_piln;

    private
        function __construct()
    {
        $this->keccakf_rndc = array(
            new o_u64(0x00000000, 0x00000001),
            new o_u64(0x00000000, 0x00008082),
            new o_u64(0x80000000, 0x0000808a),
            new o_u64(0x80000000, 0x80008000),
            new o_u64(0x00000000, 0x0000808b),
            new o_u64(0x00000000, 0x80000001),
            new o_u64(0x80000000, 0x80008081),
            new o_u64(0x80000000, 0x00008009),
            new o_u64(0x00000000, 0x0000008a),
            new o_u64(0x00000000, 0x00000088),
            new o_u64(0x00000000, 0x80008009),
            new o_u64(0x00000000, 0x8000000a),
            new o_u64(0x00000000, 0x8000808b),
            new o_u64(0x80000000, 0x0000008b),
            new o_u64(0x80000000, 0x00008089),
            new o_u64(0x80000000, 0x00008003),
            new o_u64(0x80000000, 0x00008002),
            new o_u64(0x80000000, 0x00000080),
            new o_u64(0x00000000, 0x0000800a),
            new o_u64(0x80000000, 0x8000000a),
            new o_u64(0x80000000, 0x80008081),
            new o_u64(0x80000000, 0x00008080),
            new o_u64(0x00000000, 0x80000001),
            new o_u64(0x80000000, 0x80008008)
        );
        $this->keccakf_rotc = array(
            1, 3, 6, 10,
            15, 21, 28,
            36, 45, 55,
            2, 14, 27, 41,
            56, 8, 25, 43,
            62, 18, 39, 61,
            20, 44
        );
        $this->keccakf_piln = array(
            10, 7, 11, 17,
            18, 3, 5, 16,
            8, 21, 24, 4,
            15, 23, 19, 13,
            12, 2, 20, 14,
            22, 9, 6, 1
        );
    }

    public static
        function getInstance()
    {
        if (!static::$_instance)
            static::$_instance = new self();
        return static::$_instance;
    }

    /**
     *  update the state with given number of rounds
     * @param o_u64[] $st STATE
     * @param int $rounds ROUND COUNT
     */
    public
        function keccakf(/* uint64_t[25] */array &$st, /* int */ $rounds)
    {

        /* int */
        $i     = 0;
        $j     = 0;
        $round = 0;
        /* uint64_t */
        $t     = new o_u64(0x0, 0x0);
        $bc    = array_fill(0, 5, NULL);

        for ($bc_i = 0; $bc_i < 5; ++$bc_i)
            $bc[$bc_i] = new o_u64(0x0, 0x0);

        for ($round = 0; $round < $rounds; ++$round)
        {
            // Theta

            for ($i = 0; $i < 5; ++$i)
                $bc[$i] = $st[$i]->__xor($st[$i + 5])
                    ->__xor($st[$i + 10])
                    ->__xor($st[$i + 15])
                    ->__xor($st[$i + 20]);

            for ($i = 0; $i < 5; ++$i)
            {

                $t = $bc[($i + 4) % 5]
                    ->__xor($this->ROTL64($bc[($i + 1) % 5], 1));

                $st[$i]->setxorOne($t);
                $st[$i + 5]->setxorOne($t);
                $st[$i + 10]->setxorOne($t);
                $st[$i + 15]->setxorOne($t);
                $st[$i + 20]->setxorOne($t);
            }

            // Rho Pi
            $t = clone $st[1];

            for ($i = 0; $i < 24; ++$i)
            {
                $j     = $this->keccakf_piln[$i];
                $bc[0] = clone $st[$j];

                $st[$j] = $this->ROTL64($t, $this->keccakf_rotc[$i]);

                $t = clone $bc[0];
            }

            //  Chi
            for ($j = 0; $j < 25; $j += 5)
            {

                for ($i = 0; $i < 5; ++$i)
                    $bc[$i] = clone $st[$j + $i];

                for ($i = 0; $i < 5; ++$i)
                    $st[$j + $i]->setxorOne($bc[($i + 1) % 5]->not()
                            ->__and($bc[($i + 2) % 5]));
            }


            //  Iota
            //!!! setFlip -> Flip
            $st[0]->setxorOne($this->keccakf_rndc[$round]->Flip());
        }
    }

    public
        function keccak1600(array $in, array &$md)
    {
        $this->keccak($in, count($in), $md, 200);
    }

    public
        function doHash(array $inputData, array $opts = array())
    {
        $hash = [];
        $this->keccak1600($inputData, $hash) && $opts;
        return array_slice($hash, 0, 32);
    }

    private
        function slice(&$ar, $loc, $len)
    {
        $new = array_fill(0, $len, 0);

        for ($i = 0; $i < $len; $i++)
            $new[$i] = $ar[$loc + $i];

        return $new;
    }

    /**
     * compute a keccak hash (md) of given byte length from "in"
     * @param array $in INPUT DATA
     * @param int $inlen DATA LENGTH (BYTES)
     * @param array $md HASH (OUT-REFERENCE)
     * @param int $mdlen HASH LENGTH (200)
     */
    private
        function keccak(array $in, $inlen, /* array */ &$md, $mdlen)
    {
        /* @var $st o_u64[] */
        #state_t st;

        $st = array_fill(0, 25, NULL);

        for ($st_i = 0; $st_i < 25; ++$st_i)
            $st[$st_i] = new o_u64(0x0, 0x0);

        #uint8_t temp[144];
        $temp  = array_fill(0, 144, 0);
        #$i = 0;
        #$rsiz = 0;
        #$rsizw = 0;
        $rsiz  = 200 === $mdlen ? static::HASH_DATA_AREA : (200 - 2 * $mdlen);
        $rsizw = $rsiz / 8;

        for (; $inlen >= $rsiz
        ; $inlen -= $rsiz, $in = $this->slice($in, $rsiz))
        {

            for ($i = 0; $i < $rsizw; ++$i)
                $st[$i]->setxorOne($this->___decodeLELong($in, $i * 8))
                    ->setFlip();

            $this->keccakf($st, static::KECCAK_ROUNDS);
        }

        // last block and padding
        $this->_memcpy($temp, 0, $in, 0, $inlen);

        $temp[$inlen++] = 1;

        #memset(temp + inlen, 0, rsiz - inlen);
        for ($g = 0; $g < $rsiz - $inlen; ++$g)
            $temp[$g + $inlen] = 0;

        $temp[$rsiz - 1] |= 0x80;

        for ($i = 0; $i < $rsizw; ++$i)
            $st[$i]->setxorOne($this->___decodeLELong($temp, $i * 8))
                ->setFlip();

        $this->keccakf($st, static::KECCAK_ROUNDS);

        #_memcpy($md, 0, $st, 0, $mdlen);
        for ($i = 0; $i < 25; ++$i)
            $this->___encodeBELong($st[$i], $md, $i * 8);
    }

    private
        function ROTL64(o_u64 $x, $y)
    {
        return $x->rotateLeft($y);
    }

    private
        function ___decodeLELong($buf, $off)
    {
        /* return intval(($buf[$off] & 0xFF) |
          (($buf[$off + 1] & 0xFF) << 8) |
          (($buf[$off + 2] & 0xFF) << 16) |
          (($buf[$off + 3] & 0xFF) << 24) |
          (($buf[$off + 4] & 0xFF) << 32) |
          (($buf[$off + 5] & 0xFF) << 40) |
          (($buf[$off + 6] & 0xFF) << 48) |
          (($buf[$off + 7] & 0xFF) << 56)); */
        $l = (($buf[$off] & 0xFF) |
            (($buf[$off + 1] & 0xFF) << 8) |
            (($buf[$off + 2] & 0xFF) << 16) |
            (($buf[$off + 3] & 0xFF) << 24));
        $h = ((($buf[$off + 4] & 0xFF) << 0) |
            (($buf[$off + 5] & 0xFF) << 8) |
            (($buf[$off + 6] & 0xFF) << 16) |
            (($buf[$off + 7] & 0xFF) << 24));
        return new o_u64($h, $l);
    }

    private
        function ___encodeLELong(o_u64 $val, &$buf, $off)
    {
        /* $buf[$off + 0] = BYTE(SHR($val, 0));
          $buf[$off + 1] = BYTE(SHR($val, 8));
          $buf[$off + 2] = BYTE(SHR($val, 16));
          $buf[$off + 3] = BYTE(SHR($val, 24));
          $buf[$off + 4] = BYTE(SHR($val, 32));
          $buf[$off + 5] = BYTE(SHR($val, 40));
          $buf[$off + 6] = BYTE(SHR($val, 48));
          $buf[$off + 7] = BYTE(SHR($val, 56)); */
        $buf[$off + 0] = (($val->lo >> 0) & 0xff);
        $buf[$off + 1] = (($val->lo >> 8) & 0xff);
        $buf[$off + 2] = (($val->lo >> 16) & 0xff);
        $buf[$off + 3] = (($val->lo >> 24) & 0xff);
        ##
        $buf[$off + 4] = (($val->hi >> 0) & 0xff);
        $buf[$off + 5] = (($val->hi >> 8) & 0xff);
        $buf[$off + 6] = (($val->hi >> 16) & 0xff);
        $buf[$off + 7] = (($val->hi >> 24) & 0xff);
    }

    private
        function ___encodeBELong(o_u64 $val, array &$buf, $off)
    {
        /* $buf[$off + 0] = BYTE(SHR($val, 0));
          $buf[$off + 1] = BYTE(SHR($val, 8));
          $buf[$off + 2] = BYTE(SHR($val, 16));
          $buf[$off + 3] = BYTE(SHR($val, 24));
          $buf[$off + 4] = BYTE(SHR($val, 32));
          $buf[$off + 5] = BYTE(SHR($val, 40));
          $buf[$off + 6] = BYTE(SHR($val, 48));
          $buf[$off + 7] = BYTE(SHR($val, 56)); */
        $buf[$off + 0] = (($val->hi >> 0) & 0xff);
        $buf[$off + 1] = (($val->hi >> 8) & 0xff);
        $buf[$off + 2] = (($val->hi >> 16) & 0xff);
        $buf[$off + 3] = (($val->hi >> 24) & 0xff);

        $buf[$off + 4] = (($val->lo >> 0) & 0xff);
        $buf[$off + 5] = (($val->lo >> 8) & 0xff);
        $buf[$off + 6] = (($val->lo >> 16) & 0xff);
        $buf[$off + 7] = (($val->lo >> 24) & 0xff);
        ##
    }

    private
        function _memcpy(&$arr1, $off1, $arr2, $off2, $len)
    {
        for ($i = 0; $i < $len; ++$i)
            $arr1[$off1 + $i] = $arr2[$off2 + $i];
    }

}
