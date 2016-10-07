<?php

namespace StarWars\Book;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Listing extends Command
{
    protected $db;

    public function __construct( Connection $db )
    {
        parent::__construct();

        $this->db = $db;
    }

    protected function configure()
    {
        $this
            ->setName(
                'book:list'
            )
            ->setDescription(
                'Show details about one or more books'
            )
            ->addArgument( 'name', InputArgument::OPTIONAL, 
                'Search book by title or author'
            )
        ;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = new SymfonyStyle( $input, $output );
        $query = $this->getQuery();

        if ( $name = $input->getArgument( 'name' ) ) {
            $this->setLookupName( $query, $name );
        }

        $result = $query->execute()->fetchAll();

        $this->renderResult( $io, $result );
    }

    private function getQuery()
    {
        return $this->db->createQueryBuilder()
            ->select( [
                'abbreviation',
                'title',
                'isbn',
                'authors',
            ] )
            ->from( 'Book' )
        ;
    }

    private function setLookupName( QueryBuilder $query, $name )
    {
        $query
            ->orWhere( 'title LIKE :name' )
            ->orWhere( 'authors LIKE :name' )
            ->orWhere( 'abbreviation LIKE :name' )
            ->setParameter( ':name', '%'.$name.'%', 'string' )
        ;
    }

    private function renderResult( SymfonyStyle $io, array $data )
    {
        $io->table( [ 'Abbr.', 'Title', 'ISBN', 'Authors' ], $data );
    }
}
