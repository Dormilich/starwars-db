<?php

namespace StarWars\Helper\Formatter;

interface RenderInterface
{
    public function getFormatter();

    public function setFormatter( FormatterInterface $formatter );

    public function render();
}
