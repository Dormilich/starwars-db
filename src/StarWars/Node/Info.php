<?php

namespace StarWars\Node;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Info extends Command
{
    /**
     * @var Connection $db DBAL connection object.
     */
    protected $db;

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
                'entry:info'
            )
            ->setDescription(
                'Get entry data from the database'
            )
            ->setHelp(
                'An entry is the basic information item in Star Wars Saga Edition. '.
                'It can be a Skill, Feat, Talent, Ability, etc.'
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
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = new SymfonyStyle( $input, $output );
        $id = $this->getEntry( $input );

        if ( $id > 0 ) {
            $query = $this->getQuery( $id );
            $this->renderResult( $io, $query );
        }
        else {
            $io->note( 'There is no such entry in the database' );
        }

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
     * Get the updated entry’s data.
     * 
     * @param integer $id Entry id.
     * @return QueryBuilder
     */
    private function getQuery( $id )
    {
        return $this->db->createQueryBuilder()
            ->select( [
                'n.name',
                't.name AS type',
                'n.description',
                'b.abbreviation AS book',
                'n.page',
            ] )
            ->from( 'Node', 'n' )
            ->innerJoin( 'n', 'Book', 'b', 'n.book = b.id' )
            ->innerJoin( 'n', 'NodeType', 't', 'n.type = t.id' )
            ->where( 'n.id = :id' )
            ->setParameter( ':id', $id, 'integer' )
        ;
    }

    /**
     * Display the results of the query object.
     * 
     * @param SymfonyStyle $io I/O helper object.
     * @param QueryBuilder $query Query object.
     * @return void
     */
    private function renderResult( SymfonyStyle $io, QueryBuilder $query )
    {
        $result = $query->execute()->fetch();

        $io->section( sprintf( '%s (%s, %s %d)', $result[ 'name' ], $result[ 'type' ], 
            $result[ 'book' ], $result[ 'page' ] ) );
        $io->text( $result[ 'description' ] );
        $io->newLine();
    }
}
