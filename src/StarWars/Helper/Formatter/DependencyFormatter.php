<?php

namespace StarWars\Helper\Formatter;

class DependencyFormatter extends Template
{
    public function __construct()
    {
        $template = '<amount> <name> <limit>';
        parent::__construct( $template, '<x>' );
    }
}
