<?php

namespace StarWars\Node;

use LogicException;
use RuntimeException;
use UnexpectedValueException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Add extends Command
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
                'entry:add'
            )
            ->setDescription(
                'Add a new entry into the database'
            )
            ->setHelp(
                'An entry is the basic information item in Star Wars Saga Edition. '.
                'It can be a Skill, Feat, Talent, Ability, etc.'
            )
            ->addArgument( 'type', InputArgument::REQUIRED, 
                'Name of the new entry’s type'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the new entry'
            )
            ->addOption( 'book', 'b', InputOption::VALUE_REQUIRED, 
                'Book (abbr.) that contains the entry'
            )
            ->addOption( 'page', 'p', InputOption::VALUE_REQUIRED, 
                'Page where the entry can be found'
            )
        ;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $name = $input->getArgument( 'name' );
        $type = $this->inputType( $input );
        $book = $this->inputBook( $input );
        $page = $this->inputPage( $input );

        $id = $this->createEntry( $type, $name, $book, $page );

        $io = new SymfonyStyle( $input, $output );
        $query = $this->getQuery( $id );
        $this->renderResult( $io, $query );
    }

    private function inputType( InputInterface $input )
    {
        $type = $input->getArgument( 'type' );
        $type = $this->getType( $type );

        if ( is_int( $type ) ) {
            return $type;
        }

        $msg = 'Unknown entry type.';
        throw new UnexpectedValueException( $msg );
    }

    private function inputBook( InputInterface $input )
    {
        $book = $input->getOption( 'book' );
        $book = $this->getBook( $book );

        if ( $book ) {
            return $book;
        }

        $msg = 'Invalid book abbreviation';
        throw new UnexpectedValueException( $msg );
    }

    private function inputPage( InputInterface $input )
    {
        $page = $input->getOption( 'page' );
        $page = filter_var( $page, \FILTER_VALIDATE_INT, 
            [ 'options' => [ 'min_range' => 1 ] ] );

        if ( is_int( $page ) ) {
            return $page;
        }

        $msg = 'Page is not a valid number';
        throw new UnexpectedValueException( $msg );
    }

    private function createEntry( $type, $name, $book, $page )
    {
        $ok = $this->db->insert( 'Node', [
            'name' => $name,
            'type' => $type,
            'book' => $book,
            'page' => $page,
        ], [
            'name' => 'string',
            'type' => 'integer',
            'book' => 'integer',
            'page' => 'integer',
        ] );

        if ( $ok ) {
            return $this->db->lastInsertId();
        }

        $msg = 'Failed to insert ' . $name;
        throw new RuntimeException( $msg );
    }

    private function getType( $type )
    {
        return (int) $this->db->createQueryBuilder()
            ->select( 'id' )
            ->from( 'NodeType' )
            ->where( 'name = :name' )
            ->setParameter( ':name', $type, 'string' )
            ->execute()
            ->fetchColumn()
        ;
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

    private function getQuery( $id )
    {
        return $this->db->createQueryBuilder()
            ->select( [
                't.name AS type',
                'n.name',
                'b.abbreviation',
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
        $result = $query->execute()->fetchAll();
        $io->table( [ 'Type', 'Name', 'Book', 'Page' ], $result );
    }
}
