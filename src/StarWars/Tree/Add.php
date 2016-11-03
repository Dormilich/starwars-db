<?php

namespace StarWars\Tree;

use Exception;
use ErrorException;
use StarWars\Entry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Add extends Entry
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName(
                'tree:add'
            )
            ->setDescription(
                'Add a member to a tree (or collection)'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the tree'
            )
            ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 
                'Type of the tree'
            )
            ->addOption( 'item', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 
                'Member of the tree in compact form (<type>:<name>).'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        try {
            $name = $input->getArgument( 'name' );
            $type = $input->getOption( 'type' );

            $id = $this->entry( $type, $name, 1 );

            $leafs = $input->getOption( 'item' );
            $leafs = $this->entryList( $leafs );

            array_walk( $leafs, [$this, 'addLeaf'], $id );

            $this->io->writeln('<info>' . count( $leafs ) . ' members added</info>');
        }
        catch ( Exception $e ) {
            $this->printError( $e );
            return 1;
        }

        return 0;
    }

    /**
     * Add a member to a collection.
     * 
     * @see http://php.net/array-walk
     * @param integer $leaf Id of the member entry.
     * @param integer $_ Array index.
     * @param integer $tree Id of the collection entry.
     * @return integer Affected rows.
     */
    private function addLeaf( $leaf, $_, $tree )
    {
        return $this->db->insert( 'Collection', [
            'tree' => $tree,
            'leaf' => $leaf,
        ], [ 'integer', 'integer' ]);
    }
}
