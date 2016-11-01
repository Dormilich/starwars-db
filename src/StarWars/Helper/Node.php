<?php

namespace StarWars\Helper;

use StarWars\Helper\Formatter\FormatterInterface;
use StarWars\Helper\Formatter\RenderInterface;

class Node implements RenderInterface
{
    protected $id;
    protected $name;
    protected $type;
    protected $description;
    protected $book;
    protected $page;

    /**
     * @var FormatterInterface $formatter Output formatter.
     */
    protected $formatter;

    /**
     * Returns the primary database key of this entry.
     * 
     * @return integer
     */
    public function key()
    {
        return (int) $this->id;
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
     * Run the formatter with the object's data.
     * 
     * @return string
     */
    public function render()
    {
        $data = get_object_vars( $this );
        $data = array_filter( $data, 'is_scalar' );

        $text = $this->formatter->render( $data );

        return trim( $text );
    }
}
