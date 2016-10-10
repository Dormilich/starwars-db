<?php

namespace StarWars\Node;

use UnexpectedValueException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Set extends Command
{
    protected $db;

    public function __construct( Connection $db )
    {
        $this->db = $db;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName(
                'entry:set'
            )
            ->setDescription(
                'Modify an entry in the database'
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
            ->addOption( 'book', 'b', InputOption::VALUE_REQUIRED, 
                'Book (abbr.) that contains the entry'
            )
            ->addOption( 'page', 'p', InputOption::VALUE_REQUIRED, 
                'Page where the entry can be found'
            )
            ->addOption( 'descr', 'd', InputOption::VALUE_REQUIRED, 
                'Description of the entry'
            )
        ;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = new SymfonyStyle( $input, $output );
        $id = $this->getEntry( $input );

        if ( ! $id ) {
            $io->note( 'There is no such entry in the database' );
            exit( 0 );
        }

        $data = [];
        $data[ 'book' ] = $this->inputBook( $input );
        $data[ 'page' ] = $this->inputPage( $input );
        $data[ 'description' ] = $input->getOption( 'descr' );

        $data = array_filter( $data );
        $this->updateEntry( $id, $data );

        $query = $this->getQuery( $id );
        $this->renderResult( $io, $query );
    }

    private function getEntry( InputInterface $input )
    {
        $name = $input->getArgument( 'name' );
        $type = $input->getArgument( 'type' );

        return (int) $this->db->createQueryBuilder()
            ->select( 'n.id' )
            ->from( 'Node', 'n' )
            ->innerJoin( 'n', 'NodeType', 't', 'n.type = t.id' )
            ->andWhere( 'n.name = :name' )
            ->andWhere( 't.name = :type' )
            ->setParameter( ':name', $name, 'string' )
            ->setParameter( ':type', $type, 'string' )
            ->execute()
            ->fetchColumn()
        ;
    }

    private function inputBook( InputInterface $input )
    {
        $book = $input->getOption( 'book' );

        if ( ! $book ) {
            return false;
        }

        if ( $book = $this->getBook( $book ) ) {
            return $book;
        }

        $msg = 'Invalid book abbreviation';
        throw new UnexpectedValueException( $msg );
    }

    private function inputPage( InputInterface $input )
    {
        $page = $input->getOption( 'page' );

        if ( ! $page ) {
            return false;
        }

        $page = filter_var( $page, \FILTER_VALIDATE_INT, 
            [ 'options' => [ 'min_range' => 1 ] ] );

        if ( is_int( $page ) ) {
            return $page;
        }

        $msg = 'Page is not a valid number';
        throw new UnexpectedValueException( $msg );
    }

    private function getBook( $abbr )
    {
        return (int) $this->db->createQueryBuilder()
            ->select( 'id' )
            ->from( 'Book' )
            ->where( 'abbreviation = :name' )
            ->setParameter( ':name', $abbr, 'string' )
            ->execute()
            ->fetchColumn()
        ;
    }

    private function updateEntry( $id, array $fields )
    {
        return $this->db->update( 'Node', $fields, [
            'id' => $id,
        ], [
            'id' => 'integer',
            'book' => 'integer',
            'page' => 'integer',
            'description' => 'string',
        ]);
    }

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

    private function renderResult( SymfonyStyle $io, QueryBuilder $query )
    {
        $result = $query->execute()->fetch();

        $io->section( sprintf( '%s (%s, %s %d)', $result[ 'name' ], $result[ 'type' ], 
            $result[ 'book' ], $result[ 'page' ] ) );
        $io->text( $result[ 'description' ] );
        $io->newLine();
    }
}
