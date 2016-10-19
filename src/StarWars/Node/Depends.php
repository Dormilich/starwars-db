<?php

namespace StarWars\Node;

use PDO;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use StarWars\Helper\Tree;
use StarWars\Helper\Formatter\NodeNameFormatter;
use StarWars\Helper\Formatter\NodeNameRefFormatter;
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

        $root = $this->queryNode( $id );
        $root->setFormatter( new NodeNameRefFormatter );
        $tree = new Tree( $root, $this->io );

        $dep = $this->addDependencies( $tree );

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

    // keep it simple for now
    private function getDependencies( $id )
    {
        return $this->db->createQueryBuilder()
            ->select( 'depends' )
            ->from( 'Dependency' )
            ->where( 'node = ?' )
            ->setParameter( 0, $id, 'integer' )
            ->execute()
            ->fetchAll( PDO::FETCH_COLUMN )
        ;
    }

    private function addDependencies( Tree $tree )
    {
        $id = $tree->getKey();
        $dep = $this->getDependencies( $id );

        foreach ($dep as $dep_id) {
            $node = $this->queryNode( $dep_id );
            $tree->addChild( $node );
        }

        foreach ($tree as $child) {
            $this->addDependencies( $child );
        }
    }

    /**
     * Get the updated entry’s data.
     * 
     * @param integer $id Entry id.
     * @return QueryBuilder
     */
    private function queryNode( $id )
    {
        $stmt = $this->db->createQueryBuilder()
            ->select( [
                'n.id',
                'n.name',
                't.name AS type',
                'n.description',
                'b.abbreviation AS book',
                'n.page',
            ] )
            ->from( 'Node', 'n' )
            ->innerJoin( 'n', 'Book', 'b', 'n.book = b.id' )
            ->innerJoin( 'n', 'NodeType', 't', 'n.type = t.id' )
            ->where( 'n.id = ?' )
            ->setParameter( 0, $id, 'integer' )
            ->execute()
        ;
        $stmt->setFetchMode( PDO::FETCH_CLASS, 'StarWars\\Helper\\Node' );

        return $stmt->fetch();
    }
}
