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

    /**
     * @inheritDoc
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = new SymfonyStyle( $input, $output );
        $query = $this->getQuery();

        if ( $name = $input->getArgument( 'name' ) ) {
            $this->setLookupName( $query, $name );
        }

        $this->renderResult( $io, $query );

        return 0;
    }

    /**
     * Create the selection part of the query object.
     * 
     * @return QueryBuilder
     */
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

    /**
     * Set the WHERE conditions on the query object.
     * 
     * @param QueryBuilder $query Query object.
     * @param string $name Search string.
     * @return void
     */
    private function setLookupName( QueryBuilder $query, $name )
    {
        $query
            ->orWhere( 'title LIKE :name' )
            ->orWhere( 'authors LIKE :name' )
            ->orWhere( 'abbreviation LIKE :name' )
            ->setParameter( ':name', '%'.$name.'%', 'string' )
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
        $result = $query->execute()->fetchAll();
        $io->table( [ 'Abbr.', 'Title', 'ISBN', 'Authors' ], $result );
    }
}
