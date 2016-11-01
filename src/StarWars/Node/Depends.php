<?php

namespace StarWars\Node;

use PDO;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use StarWars\Helper\Fork;
use StarWars\Helper\Tree;
use StarWars\Helper\Formatter\DependencyFormatter;
use StarWars\Helper\Formatter\DependencyRefFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Depends extends Command
{
    /**
     * @var Connection $db DBAL connection object.
     */
    protected $db;

    /**
     * @var SymfonyStyle $io Output formatter.
     */
    protected $io;

    /**
     * Set up the command.
     * 
     * @param Connection $db DBAL connection object.
     * @return self
     */
    public function __construct( Connection $db )
    {
        $this->db = $db;

        parent::__construct();
    }

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
            ->addArgument( 'type', InputArgument::REQUIRED, 
                'Name of the entry’s type'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the entry'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        $this->io = new SymfonyStyle( $input, $output );
    }

    /**
     * @inheritDoc
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $id = $this->getEntry( $input );

        if ( $id === 0 ) {
            $this->io->note( 'There is no such entry in the database' );
            return 0;
        }

        $formatter = $output->isVerbose() 
            ? new DependencyRefFormatter 
            : new DependencyFormatter
        ;
        $formatter->defaultValue = '';

        $root = $this->queryNode( $id );
        $root->setFormatter( $formatter );
        
        $tree = new Tree( $root, $this->io );

        $this->addDependencies( $tree );

        $tree->render();

        return 0;
    }

    /**
     * Get the entry id from the provided input. Returns `0` if there is no match.
     * 
     * @param InputInterface $input Input object.
     * @return integer Entry id.
     */
    private function getEntry( InputInterface $input )
    {
        $name = $input->getArgument( 'name' );
        $type = $input->getArgument( 'type' );

        return (int) $this->db->createQueryBuilder()
            ->select( 'n.id' )
            ->from( 'Node', 'n' )
            ->innerJoin( 'n', 'NodeType', 't', 'n.type = t.id' )
            ->andWhere( 'n.name LIKE :name' )
            ->andWhere( 't.name LIKE :type' )
            ->setParameter( ':name', $name, 'string' )
            ->setParameter( ':type', $type, 'string' )
            ->execute()
            ->fetchColumn()
        ;
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
            ->select(
                'depends',
                'min_value',
                'min_count'
            )
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
            ->select(
                'group_id',
                'depends',
                'min_value',
                'min_count'
            )
            ->from( 'Dependency' )
            ->andWhere( 'group_id IS NOT NULL' )
            ->andWhere( 'node = ?' )
            ->setParameter( 0, $id, 'integer' )
            ->execute()
            ->fetchAll( PDO::FETCH_GROUP )
        ;
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
            $node = $this->queryNode( $row[ 'depends' ] );
            $node->setLimit( $row[ 'min_value' ] );
            $node->setAmount( $row[ 'min_count' ] );

            return $node;
        }, $data );
    }

    /**
     * Get the node object for the given entry.
     * 
     * @param integer $id Entry id.
     * @return Node
     */
    private function queryNode( $id )
    {
        $stmt = $this->db->createQueryBuilder()
            ->select( [
                'n.id',
                'n.name',
                't.name AS type',
                'n.description',
                'b.short AS book',
                'n.page',
            ] )
            ->from( 'Node', 'n' )
            ->innerJoin( 'n', 'Book', 'b', 'n.book = b.id' )
            ->innerJoin( 'n', 'NodeType', 't', 'n.type = t.id' )
            ->where( 'n.id = ?' )
            ->setParameter( 0, $id, 'integer' )
            ->execute()
        ;
        // classes cannot be set in fetch()
        $stmt->setFetchMode( PDO::FETCH_CLASS, 'StarWars\\Helper\\DepNode' );

        return $stmt->fetch();
    }
}
