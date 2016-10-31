<?php

namespace StarWars\Helper;

use StarWars\Helper\Formatter\FormatterInterface;

class Node
{
    protected $id;
    protected $name;
    protected $type;
    protected $description;
    protected $book;
    protected $page;

    protected $formatter;

    public function key()
    {
        return (int) $this->id;
    }

    public function getFormatter()
    {
        return $this->formatter;
    }

    public function setFormatter( FormatterInterface $formatter )
    {
        $this->formatter = $formatter;
    }

    public function render()
    {
        return $this->formatter->render( [
            'name' => $this->name,
            'type' => $this->type,
            'book' => $this->book,
            'page' => $this->page,
        ] );
    }

    public function __toString()
    {
        return $this->render();
    }
}
