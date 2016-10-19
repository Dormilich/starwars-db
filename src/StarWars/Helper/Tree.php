<?php

namespace StarWars\Helper;

use Symfony\Component\Console\Output\OutputInterface;

class Tree extends Composite
{
    private $output;
    private $branch;

    protected $indent     = ' │   ';
    protected $lastIndent = '     ';

    protected $limb       = ' ├── ';
    protected $lastLimb   = ' └── ';

    public function __construct( Node $node, OutputInterface $output )
    {
        $this->branch = $node;
        $this->output = $output;
    }

    public function addChild( Node $node )
    {
        $formatter = $this->branch->getFormatter();
        $node->setFormatter( $formatter );
        $child = new Tree( $node, $this->output );
        $this->add( $child );
    }

    public function getKey()
    {
        return $this->branch->id();
    }

    // Iterator
    public function key()
    {
        return $this->current()->getKey();
    }

    public function render( $indent = '', $limb = '' )
    {
        $this->renderBranch( $indent, $limb );

        // true except for the root node
        if ( $limb !== $indent ) {
            $indent .= $this->indent;
        }

        $this->renderChildren( $indent );
    }

    public function renderLast( $indent, $limb )
    {
        $this->renderBranch( $indent, $limb );

        $indent .= $this->lastIndent;

        $this->renderChildren( $indent );
    }

    private function renderBranch( $indent, $limb )
    {
        $line = $indent . $limb . $this->branch->render();
        $this->output->writeln( $line );
    }

    private function renderChildren( $indent )
    {
        $children = $this->getChildren();
        $last = array_pop( $children );

        foreach ( $children as $index => $child) {
            $child->render( $indent, $this->limb );
        }

        if ( $last ) {
            $last->renderLast( $indent, $this->lastLimb );
        }
    }
}
