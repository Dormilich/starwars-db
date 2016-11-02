<?php

namespace StarWars\Node;

use Exception;
use RuntimeException;
use UnexpectedValueException;
use StarWars\Entry;
use Doctrine\DBAL\Query\QueryBuilder;
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

    /**
     * @inheritDoc
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        try {
            $name = $input->getArgument( 'name' );
            $type = $this->inputType( $input );
            $book = $this->inputBook( $input );
            $page = $this->inputPage( $input );

            $id = $this->createEntry( $type, $name, $book, $page );

            $query = $this->getQuery( $id );
            $this->renderResult( $query );

        }
        catch ( Exception $e ) {
            $this->printError( $e );
            return 1;
        }

        return 0;
    }

    /**
     * Get the entry type from the input source.
     * 
     * @param InputInterface $input Input object.
     * @return integer Entry type id.
     * @throws UnexpectedValueException Unknown type name.
     */
    private function inputType( InputInterface $input )
    {
        $type = $input->getArgument( 'type' );
        $type = $this->getType( $type );

        if ( $type > 0 ) {
            return $type;
        }

        $msg = 'Unknown entry type.';
        throw new UnexpectedValueException( $msg );
    }

    /**
     * Get the book from the input source.
     * 
     * @param InputInterface $input Input object.
     * @return integer Book id.
     * @throws UnexpectedValueException Invalid book.
     */
    private function inputBook( InputInterface $input )
    {
        $book = $input->getOption( 'book' );
        $book = $this->getBook( $book );

        if ( $book > 0 ) {
            return $book;
        }

        $msg = 'Invalid book abbreviation';
        throw new UnexpectedValueException( $msg );
    }

    /**
     * Get the page number from the input source.
     * 
     * @param InputInterface $input Input object.
     * @return integer Page number.
     * @throws UnexpectedValueException Invalid page.
     */
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

    /**
     * Insert entry data into the database.
     * 
     * @param integer $type Entry type id.
     * @param string $name Entry name.
     * @param integer $book Book id.
     * @param integer $page Page number.
     * @return integer Entry id.
     * @throws RuntimeException Insertion failed.
     */
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

    /**
     * Get the type id from the type name. Returns `0` if there is no such type.
     * 
     * @param string $type Entry type name.
     * @return integer Entry type id.
     */
    private function getType( $type )
    {
        return (int) $this->db->createQueryBuilder()
            ->select( 'id' )
            ->from( 'NodeType' )
            ->where( 'name LIKE :name' )
            ->setParameter( ':name', $type, 'string' )
            ->execute()
            ->fetchColumn()
        ;
    }

    /**
     * Get the book id from the book abbreviation. Returns `0` if there is no match.
     * 
     * @param string $book Book abbreviation.
     * @return integer Book id.
     */
    private function getBook( $abbr )
    {
        return (int) $this->db->createQueryBuilder()
            ->select( 'id' )
            ->from( 'Book' )
            ->where( 'abbreviation LIKE :name' )
            ->setParameter( ':name', $abbr, 'string' )
            ->execute()
            ->fetchColumn()
        ;
    }

    /**
     * Get the added entry’s data.
     * 
     * @param integer $id Entry id.
     * @return QueryBuilder
     */
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

    /**
     * Display the results of the query object.
     * 
     * @param SymfonyStyle $this->io I/O helper object.
     * @param QueryBuilder $query Query object.
     * @return void
     */
    private function renderResult( QueryBuilder $query )
    {
        $result = $query->execute()->fetchAll();
        $this->io->table( [ 'Type', 'Name', 'Book', 'Page' ], $result );
    }
}
