<?php

namespace StarWars\Tree;

use PDO;
use Exception;
use ErrorException;
use StarWars\Entry;
use StarWars\Helper\Tree;
use StarWars\Helper\Formatter\NodeNameFormatter;
use StarWars\Helper\Formatter\NodeNameRefFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Show extends Entry
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName(
                'tree:list'
            )
            ->setDescription(
                'List the contents of a tree or collection'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the tree or collection'
            )
            ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 
                'Type of the tree or collection'
            )
            ->addOption( 'depth', null, InputOption::VALUE_REQUIRED, 
                'The number of nested levels to show'
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
            $nest = $input->getOption( 'depth' );
            $nest = filter_var( $nest, \FILTER_VALIDATE_INT );

            $id = $this->getEntry( $type, $name );

            if ( ! $id ) {
                throw new ErrorException( 'There is no such entry in the database.', 0, 0 );
            }

            $formatter = $output->isVerbose() 
                ? new NodeNameRefFormatter 
                : new NodeNameFormatter
            ;

            $root = $this->getNode( $id );
            $root->setFormatter( $formatter );
            
            $tree = new Tree( $root, $this->io );

            $this->addChildren( $tree, 0, $nest );

            $tree->render();
        }
        catch ( Exception $e ) {
            $this->printError( $e );
            return 1;
        }

        return 0;
    }

    private function addChildren( Tree $tree, $_, $nesting )
    {
        if ( $nesting === 0 ) {
            return $tree;
        }
        if ( is_int( $nesting ) ) {
            $nesting--;
        }

        $id = $tree->getKey();

        $ids = $this->getCollection( $id );
        $nodes = array_map( [$this, 'getNode'], $ids );
        array_walk( $nodes, [$tree, 'addChild'] );

        $children = $tree->getChildren();
        array_walk( $children, [$this, 'addChildren'], $nesting );

        return $tree;
    }

    private function getCollection( $id )
    {
        return $this->db->createQueryBuilder()
            ->select( 'c.leaf' )
            ->from( 'Collection', 'c' )
            ->innerJoin( 'c', 'Node', 'n', 'c.leaf = n.id' )
            ->where( 'c.tree = ?' )
            ->orderBy( 'n.name', 'asc' )
            ->setParameter( 0, $id, 'integer' )
            ->execute()
            ->fetchAll( PDO::FETCH_COLUMN )
        ;
    }
}
