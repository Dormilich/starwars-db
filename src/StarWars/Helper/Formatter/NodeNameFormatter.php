<?php

namespace StarWars\Helper\Formatter;

class NodeNameFormatter extends Template
{
    public function __construct()
    {
        $template = '{{ name }} ({{ type }})';
        parent::__construct( $template, '{{ ', ' }}' );
    }
}
