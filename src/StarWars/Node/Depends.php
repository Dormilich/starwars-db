<?php

namespace StarWars\Node;

use PDO;
use StarWars\Entry;
use StarWars\Helper\Fork;
use StarWars\Helper\Tree;
use StarWars\Helper\Formatter\DependencyFormatter;
use StarWars\Helper\Formatter\DependencyRefFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Depends extends Entry
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName(
                'entry:depends'
            )
            ->setDescription(
                'Show the dependency tree for an entry'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the entry'
            )
            ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 
                'Type of the entry'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $name = $input->getArgument( 'name' );
        $type = $input->getOption( 'type' );

        $id = $this->getEntry( $type, $name );

        if ( ! $id ) {
            $this->io->note( 'There is no such entry in the database' );
            return 0;
        }

        $formatter = $output->isVerbose() 
            ? new DependencyRefFormatter 
            : new DependencyFormatter
        ;

        $root = $this->getNode( $id );
        $root->setFormatter( $formatter );
        
        $tree = new Tree( $root, $this->io );

        $this->addDependencies( $tree );

        $tree->render();

        return 0;
    }

    /**
     * Recursively add dependencies.
     * 
     * @param Tree $tree Composite object.
     * @return Tree
     */
    private function addDependencies( Tree $tree )
    {
        $id = $tree->getKey();

        $dep = $this->getDependencies( $id );
        $dep = $this->addNodes( $dep );

        array_walk( $dep, [$tree, 'addChild'] );

        $children = $tree->getChildren();
        array_walk( $children, [$this, 'addDependencies'] );

        $this->addGroupDependencies( $tree );

        return $tree;
    }

    /**
     * Add dependency groups. Note that these dependencies are not resolved 
     * further even if a member of the group had dependencies.
     * 
     * @param Tree $tree 
     * @return Tree
     */
    private function addGroupDependencies( Tree $tree )
    {
        $id = $tree->getKey();

        $group = $this->getGroupDependencies( $id );

        foreach ($group as $gid => $items ) {
            $nodes = $this->addNodes( $items );
            $tree->addChild( new Fork( $nodes ) );
        }

        return $tree;
    }

    /**
     * Add data objects to the data array.
     * 
     * @param array $data 
     * @return array
     */
    private function addNodes( array $data )
    {
        return array_map( function ( array $row ) {
            $node = $this->getNode( $row[ 'depends' ] );
            $node->setLimit( $row[ 'min_value' ] );
            $node->setAmount( $row[ 'min_count' ] );

            return $node;
        }, $data );
    }

    /**
     * Get the dependencies that are not part of a group.
     * 
     * @param integer $id 
     * @return array
     */
    private function getDependencies( $id )
    {
        return $this->db->createQueryBuilder()
            ->select( [
                'depends',
                'min_value',
                'min_count',
            ] )
            ->from( 'Dependency' )
            ->andWhere( 'group_id IS NULL' )
            ->andWhere( 'node = ?' )
            ->setParameter( 0, $id, 'integer' )
            ->execute()
            ->fetchAll()
        ;
    }

    /**
     * Get the dependencies that are part of a group.
     * 
     * @param integer $id 
     * @return array
     */
    private function getGroupDependencies( $id )
    {
        return $this->db->createQueryBuilder()
            ->select( [
                'group_id',
                'depends',
                'min_value',
                'min_count',
            ] )
            ->from( 'Dependency' )
            ->andWhere( 'group_id IS NOT NULL' )
            ->andWhere( 'node = ?' )
            ->setParameter( 0, $id, 'integer' )
            ->execute()
            ->fetchAll( PDO::FETCH_GROUP )
        ;
    }
}
