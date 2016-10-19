<?php

namespace StarWars\Helper;

use Countable;
use Iterator;

abstract class Composite implements Countable, Iterator
{
    private $children = [];

    public function getChildren()
    {
        return $this->children;
    }

    public function add( Composite $obj )
    {
        $this->children[] = $obj;
    }

    public function remove( Composite $obj )
    {
        if ( false !== $key = array_search( $obj, $this->children, true ) ) {
            array_splice( $this->children, $offset, 1 );
        }
    }

    public function count()
    {
        return count( $this->children );
    }

    public function rewind()
    {
        reset( $this->children );
    }

    public function current()
    {
        return current( $this->children );
    }

    public function key()
    {
        return key( $this->children );
    }

    public function next()
    {
        next( $this->children );
    }

    public function valid()
    {
        return null !== key( $this->children );
    }
}
