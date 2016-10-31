<?php

namespace StarWars\Helper\Formatter;

class NodeNameFormatter extends Template
{
    public function __construct()
    {
        $template = '<name>';
        parent::__construct( $template, '<x>' );
    }
}
