<?php

namespace StarWars\Helper;

class DepNode extends Node
{
    protected $limit;
    protected $amount;

    public function setLimit( $limit )
    {
        if ( $limit > 0 ) {
            $this->limit = $limit . '+';
        }

        return $this;
    }

    public function setAmount( $amount )
    {
        if ( $amount ) {
            $this->amount = $amount . ' from';
        }

        return $this;
    }
}
