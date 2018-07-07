<?php

interface ptr extends ArrayAccess {

    /**
     * TODO immutable
     * сдвинуть на 1 элемент + или -
     * @param type $v
     * @return New ptr
     */
    public function move($v);
}

//элементы любого типа
class void_ptr implements ptr {

    /**
     * массив элементов
     * @var array[]
     */
    protected $elements;

    /**
     * кол-во ЭЛЕМЕНТОВ
     * @var int
     */
    protected $size;

    /**
     * смещение в ЭЛЕМЕНТАХ
     * @var int
     */
    protected $baseOffset = 0;

    protected function __construct($size, $alloc = TRUE) {

        if ($size < 0)
            throw new Exception('Negative size.');

        $this->size = $size;
        $alloc && ($this->elements = array_fill(0, $this->size, NULL));
    }

    public function offsetExists($offset) {
        throw new Exception('Unsupported operation');
    }

    public function offsetGet($idx) {

        $idx += $this->baseOffset;

        if ($idx < 0)
            throw new Exception('negative idx');
        if ($idx >= $this->size)
            throw new Exception('No more memory.');

        return $this->elements[$idx];
    }

    public function offsetSet($idx, $val) {
        $idx += $this->baseOffset;
        if ($idx < 0)
            throw new Exception('negative idx');
        if ($idx >= $this->size)
            throw new Exception('No more memory.');

        $this->elements[$idx] = $val;
    }

    public function offsetUnset($offset) {
        throw new Exception('Unsupported operation');
    }

    public function move($v) {
        /* $off = $this->baseOffset;
          $off += $v;

          if ($off < 0)
          throw new Exception('Negative base offset!!!');
          else if ($off > $this->size)
          throw new Exception('Out of bounds!!!');

          $this->baseOffset = $off;
          return $this; */
        $p = new void_ptr($this->size - $v, TRUE);

        for ($i = $v, $j = 0; $i < $this->size; ++$i, ++$j)
            $p->elements[$j] = &$this->elements[$i];

        return $p;
    }

    /**
     * 
     * @param type $size кол-во элементов
     * @return \void_ptr
     */
    public static function alloc($size) {
        return new void_ptr($size, TRUE);
    }

    public static function copy(array $data) {
        $mem = new void_ptr(count($data), FALSE);
        $mem->elements = $data; //copy data
        return $mem;
    }

}

final class uint8_ptr extends void_ptr implements ptr {

    public function __construct(void_ptr $mem) {
        parent::__construct(0, FALSE);

        for ($i = $mem->baseOffset; $i < $mem->size; ++$i) {
            $this->elements[] = &$mem->elements[$i];
            ++$this->size;
        }
    }

    public function offsetGet($idx) {

        $idx += $this->baseOffset;

        if ($idx < 0)
            throw new Exception('negative idx');
        if ($idx >= $this->size)
            throw new Exception('No more memory.');

        return $this->elements[$idx] & 0xff;
    }

    public function offsetSet($idx, $val) {
        $idx += $this->baseOffset;
        if ($idx < 0)
            throw new Exception('negative idx');
        if ($idx >= $this->size)
            throw new Exception('No more memory.');

        $this->elements[$idx] = ($val & 0xff);
    }

    public function cast_to_uint32_ptr() {

        if (($this->size % 4) !== 0)
            throw new Exception('should be rounded by four!');

        return new uint32_ptr($this);
    }

    public function __toString() {
        $str = implode('', array_map('chr', $this->elements));
        return wordwrap(bin2hex($str), 2, ' ', TRUE);
    }

    public function move($v) {
        /* $off = $this->baseOffset;
          $off += $v;

          if ($off < 0)
          throw new Exception('Negative base offset!!!');
          else if ($off > $this->size)
          throw new Exception('Out of bounds!!!');

          $this->baseOffset = $off;
          return $this; */
        $p = new uint8_ptr(new void_ptr($this->size - $v, TRUE));

        for ($i = $v, $j = 0; $i < $this->size; ++$i, ++$j)
            $p->elements[$j] = &$this->elements[$i];

        return $p;
    }

    /**
     * 
     * @param type $size кол-во байт
     * @return \uint8_ptr
     */
    public static function alloc($size) {
        $p = void_ptr::alloc($size);
        for ($i = 0; $i < $size; ++$i)
            $p[$i] = 0x00;
        return new uint8_ptr($p);
    }

}

// LE
final class uint32_ptr extends void_ptr implements ptr {

    /**
     *
     * @var void_ptr
     */
    private $mem;

    public function __construct(void_ptr $mem) {
        parent::__construct($mem->size / 4, FALSE);
        $this->mem = $mem;
        $this->size -= ($mem->baseOffset / 4);
    }

    public function offsetGet($idx) {

        $idx += $this->baseOffset;

        if ($idx < 0)
            throw new Exception('negative idx');
        if ($idx >= $this->size)
            throw new Exception('No more memory.');

        $idx *= 4;

        return ((($this->mem[$idx++] & 0xff) << 0) |
                (($this->mem[$idx++] & 0xff) << 8) |
                (($this->mem[$idx++] & 0xff) << 16) |
                (($this->mem[$idx++] & 0xff) << 24)) & 0xffffffff;
    }

    public function offsetSet($idx, $val) {

        $idx += $this->baseOffset;

        if ($idx < 0)
            throw new Exception('negative idx');
        if ($idx >= $this->size)
            throw new Exception('No more memory.');

        $idx *= 4;

        $val &= 0xffffffff;

        $this->mem[$idx++] = ($val >> 0) & 0xff;
        $this->mem[$idx++] = ($val >> 8) & 0xff;
        $this->mem[$idx++] = ($val >> 16) & 0xff;
        $this->mem[$idx++] = ($val >> 24) & 0xff;
    }

    public function cast_to_uint8_ptr() {
        return ($this->mem instanceof uint8_ptr ?
                $this->mem :
                new uint8_ptr($this->mem));
    }

    public function __toString() {

        $arr = [];

        for ($i = 0; $i < $this->size - $this->baseOffset; ++$i)
            $arr[] = sprintf('0x%08x', $this->offsetGet($i));

        return implode(' ', $arr);
    }

    public function move($v) {
        throw new Exception('Fuck off plz');
    }

    /**
     * 
     * @param type $size кол-во uint32
     * @return \uint8_ptr
     */
    public static function alloc($size) {
        $p = void_ptr::alloc($size * 4);
        for ($i = 0; $i < $size * 4; ++$i)
            $p[$i] = 0x00000000;
        return new uint32_ptr($p);
    }

}

/*$p32 = new uint32_ptr(void_ptr::alloc(16));
$p8 = $p32->cast_to_uint8_ptr();

$p32[0] = 0x7fffffff;
$p8[3] = 0;

var_dump((string) $p8);
var_dump((string) $p32);
die;*/
