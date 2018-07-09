<?php

namespace Shift196\AKashLib\Util;

final
    class UInt64Pool
{

    private static
        $_free = [];

    /**
     * 
     * @return UnsignedInt64
     */
    public static
        function getObject()
    {

        if (NULL === ($ou = array_pop(static::$_free)))
            $ou = new UnsignedInt64(0x0, 0x0);

        return $ou;
    }

    public static
        function returnObject(UnsignedInt64 $object)
    {

        array_push(static::$_free, $object);
    }

}
