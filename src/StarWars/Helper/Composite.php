<?php

namespace StarWars\Helper;

use Countable;
use Iterator;

/**
 * Base class of a tree structure (Composite Pattern). It is responsible for 
 * creating the tree structure and providing helper methods for traversing it.
 */
class Composite implements Countable, Iterator
{
    /**
     * @var array $children List of child nodes.
     */
    private $children = [];

    /**
     * Add a child node.
     * 
     * @param Composite $obj 
     * @return void
     */
    public function add( Composite $obj )
    {
        $this->children[] = $obj;
    }

    /**
     * Remove a child node.
     * 
     * @param Composite $obj 
     * @return integer The index of the removed object.
     */
    public function remove( Composite $obj )
    {
        if ( false !== $key = array_search( $obj, $this->children, true ) ) {
            array_splice( $this->children, $key, 1 );
        }

        return $key;
    }

    /**
     * Check if there are child nodes defined.
     * 
     * @return boolean True if there are children defined. 
     */
    public function hasChildren()
    {
        return $this->count() > 0;
    }

    /**
     * Get the list of child nodes.
     * 
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @see http://php.net/Countable
     */
    public function count()
    {
        return count( $this->children );
    }

    /**
     * @see http://php.net/Iterator
     */
    public function rewind()
    {
        reset( $this->children );
    }

    /**
     * @see http://php.net/Iterator
     */
    public function current()
    {
        return current( $this->children );
    }

    /**
     * @see http://php.net/Iterator
     */
    public function key()
    {
        return key( $this->children );
    }

    /**
     * @see http://php.net/Iterator
     */
    public function next()
    {
        next( $this->children );
    }

    /**
     * @see http://php.net/Iterator
     */
    public function valid()
    {
        return null !== key( $this->children );
    }
}
