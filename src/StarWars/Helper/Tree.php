<?php

namespace StarWars\Helper;

use StarWars\Helper\Formatter\RenderInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Tree extends Composite
{
    /**
     * @var OutputInterface $output Rendering context.
     */
    private $output;

    /**
     * @var Node $branch The data object labelling the current branch of the tree.
     */
    private $branch;

    /**
     * @var string $indent Indentation of child nodes when the branch is not a last-child.
     */
    protected $indent     = ' │   ';

    /**
     * @var string $lastIndent Indentation of child nodes when the branch is a last-child.
     */
    protected $lastIndent = '     ';

    /**
     * @var string $limb Branch prefix when the branch is not a last-child.
     */
    protected $limb       = ' ├── ';

    /**
     * @var string $lastLimb Branch prefix when the branch is a last-child.
     */
    protected $lastLimb   = ' └── ';

    /**
     * Set objects for branch node and output. 
     * 
     * @param Node $node 
     * @param OutputInterface $output 
     * @return self
     */
    public function __construct( RenderInterface $node, OutputInterface $output )
    {
        $this->branch = $node;
        $this->output = $output;
    }

    /**
     * Wrap a node object into a composite and add it as child.
     * 
     * @param Node $node 
     * @return void
     */
    public function addChild( RenderInterface $node )
    {
        $formatter = $this->branch->getFormatter();
        $node->setFormatter( $formatter );

        $child = new Tree( $node, $this->output );
        $this->add( $child );
    }

    /**
     * Get the primary key for the branch node.
     * 
     * @return type
     */
    public function getKey()
    {
        return $this->branch->key();
    }

    /**
     * Instead of returning the index of the child node in the list, return its 
     * (database) key. This method should not be called explicitly!
     * 
     * @see http://php.net/Iterator
     * 
     * @return integer Primary key.
     */
    public function key()
    {
        return $this->current()->getKey();
    }

    /**
     * Render the branch and its children (but the last). To start rendering the 
     * tree this method is called without parameters from the tree's root. 
     * 
     * @param string|'' $indent Characters to prepend to all nodes to render.
     * @param string|'' $limb Characters to prepend to the branch node output.
     * @return void
     */
    public function render( $indent = '', $limb = '' )
    {
        $this->renderBranch( $indent, $limb );

        // true except for the root node
        if ( $limb !== $indent ) {
            $indent .= $this->indent;
        }

        $this->renderChildren( $indent );
    }

    /**
     * Render the last child of a parent node's children. These nodes need 
     * different indentations.
     * 
     * @param string $indent Characters to prepend to all nodes to render.
     * @param string $limb Characters to prepend to the branch node output.
     * @return void
     */
    public function renderLast( $indent, $limb )
    {
        $this->renderBranch( $indent, $limb );

        $indent .= $this->lastIndent;

        $this->renderChildren( $indent );
    }

    /**
     * Render the tree's branch node.
     * 
     * @param string $indent Characters to prepend to all nodes to render.
     * @param string $limb Characters to prepend to the branch node output.
     * @return void
     */
    private function renderBranch( $indent, $limb )
    {
        $line = $indent . $limb . $this->branch->render();
        $this->output->writeln( $line );
    }

    /**
     * Render the tree's children.
     * 
     * @param string $indent Characters to prepend to all child nodes.
     * @return void
     */
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
