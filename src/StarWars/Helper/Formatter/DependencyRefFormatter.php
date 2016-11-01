<?php

namespace StarWars\Helper\Formatter;

class DependencyRefFormatter extends Template
{
    public function __construct()
    {
        $template = '<amount> <name> <limit> (<type>, <book> p.<page>)';
        parent::__construct( $template, '<x>' );
    }
}
