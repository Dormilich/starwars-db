<?php

namespace StarWars\Helper\Formatter;

interface FormatterInterface
{
    public function getTemplate();

    public function setTemplate( $template );

    public function assign( $name, $value );

    public function render( array $data = [] );
}
