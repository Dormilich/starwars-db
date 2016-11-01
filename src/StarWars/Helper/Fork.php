<?php

namespace StarWars\Helper;

use Countable;
use StarWars\Helper\Formatter\FormatterInterface;
use StarWars\Helper\Formatter\RenderInterface;

class Fork implements RenderInterface, Countable
{
    /**
     * @var RenderInterface[] $nodes A collection of renderable entries.
     */
    protected $nodes = [];

    /**
     * @var string $separator The string to concatenate the rendered nodes.
     */
    protected $separator = ' | ';

    /**
     * @var FormatterInterface $formatter Output formatter.
     */
    protected $formatter;

    /**
     * Add the first entry to the list.
     * 
     * @param RenderInterface $node A renderable entry.
     * @return self
     */
    public function __construct( $data )
    {
        if ( is_array( $data ) ) {
            $this->set( $data );
        }
        else {
            $this->add( $data );
        }
    }

    /**
     * To be compliant with Node.
     * 
     * @return null
     */
    public function key()
    {
        return null;
    }

    /**
     * Add an entry to the list. Since the formatter can be changed at any time, 
     * it makes no sense to set the formatter here.
     * 
     * @param RenderInterface $node A renderable entry.
     * @return self
     */
    public function add( RenderInterface $node )
    {
        $this->nodes[] = $node;

        return $this;
    }

    /**
     * Set all entries at once. Overwrites any previous entries.
     * 
     * @param array $nodes A list of renderable entries.
     * @return self
     */
    public function set( array $nodes )
    {
        $this->nodes = [];

        foreach ( $nodes as $node ) {
            $this->add( $node );
        }

        return $this;
    }

    /**
     * Remove a node from the list.
     * 
     * @param RenderInterface $node A renderable entry.
     * @return integer The index of the removed object.
     */
    public function remove( RenderInterface $node )
    {
        if ( false !== $key = array_search( $node, $this->nodes, true ) ) {
            array_splice( $this->nodes, $key, 1 );
        }

        return $key;
    }

    /**
     * @see http://php.net/Countable
     */
    public function count()
    {
        return count( $this->nodes );
    }

    /**
     * Get the formatter object.
     * 
     * @return FormatterInterface
     */
    public function getFormatter()
    {
        return $this->formatter;
    }

    /**
     * Set the formatter object.
     * 
     * @param FormatterInterface $formatter 
     * @return self
     */
    public function setFormatter( FormatterInterface $formatter )
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Set the string to separate the rendered nodes.
     * 
     * @param string $separator 
     * @return self
     */
    public function setSeparator( $separator )
    {
        $this->separator = (string) $separator;

        return $this;
    }

    /**
     * Run the formatter with the object's nodes and return the concatenated result.
     * 
     * @return string
     */
    public function render()
    {
        foreach ( $this->nodes as $node ) {
            $node->setFormatter( $this->formatter );
        }

        $list = array_map( function ( $node ) {
            return $node->render();
        }, $this->nodes );

        $text = implode( $this->separator, $list );

        return $text;
    }
}
