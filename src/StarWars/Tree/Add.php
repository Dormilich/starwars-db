<?php

namespace StarWars\Tree;

use PDO;
use Exception;
use ErrorException;
use Doctrine\DBAL\Connection;
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

            $id = $this->getEntry( $type, $name );

            if ( ! $id ) {
                throw new ErrorException( 'There is no such entry in the database.', 0, 1 );
            }

            $leafs = $input->getOption( 'item' );
            $leafs = $this->getIds( $leafs );

            array_walk( $leafs, [$this, 'addLeaf'], $id );

            $this->io->writeln('<info>' . count( $leafs ) . ' members added</info>');
        }
        catch ( Exception $e ) {
            $this->printError( $e );
            return 1;
        }

        return 0;
    }

    private function getIds( array $names )
    {
        $parts = array_map( function ( $value ) {
            $parts = explode( ':', $value, 2 );
            if ( count( $parts ) === 1 ) {
                array_unshift( $parts, false );
            }
            return $parts;
        }, $names );

        // for some reason array_map() does not like exceptions from called methods
        $ids = array_map( function ( array $list ) {
            try {
                list( $type, $name ) = $list;
                return $this->getEntry( $type, $name );
            } catch ( Exception $e ) {
                $this->printError( $e );
                return false;
            }
        }, $parts );

        $ids = array_filter( $ids );

        return $ids;
    }

    private function addLeaf( $leaf, $index, $tree )
    {
        return $this->db->insert( 'Collection', [
            'tree' => $tree,
            'leaf' => $leaf,
        ], [ 'integer', 'integer' ]);
    }
}
