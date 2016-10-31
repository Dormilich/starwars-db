<?php

namespace StarWars\Helper\Formatter;

class NodeNameRefFormatter extends Template
{
    public function __construct()
    {
        $template = '<name> (<type>, <book> p.<page>)';
        parent::__construct( $template, '<x>' );
    }
}
