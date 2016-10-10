<?php

namespace StarWars\Node;

use Exception;
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

    /**
     * @inheritDoc
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = new SymfonyStyle( $input, $output );
        try {
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

        } catch (Exception $e) {
            $io->error( $e->getMessage() );
            if ( $output->isVerbose() ) {
                $io->listing( $e->getTrace() );
            }
        }
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
            ->andWhere( 'n.name = :name' )
            ->andWhere( 't.name = :type' )
            ->setParameter( ':name', $name, 'string' )
            ->setParameter( ':type', $type, 'string' )
            ->execute()
            ->fetchColumn()
        ;
    }

    /**
     * Get the book from the input source.
     * 
     * @param InputInterface $input Input object.
     * @return integer|false Book id.
     */
    private function inputBook( InputInterface $input )
    {
        $book = $input->getOption( 'book' );

        if ( ! $book ) {
            return false;
        }

        if ( $book = $this->getBook( $book ) ) {
            return (int) $book;
        }

        $msg = 'Invalid book abbreviation';
        throw new UnexpectedValueException( $msg );
    }

    /**
     * Get the page number from the input source.
     * 
     * @param InputInterface $input Input object.
     * @return integer|false Page number.
     */
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

    /**
     * Get the book id from the book abbreviation. Returns `0` if there is no match.
     * 
     * @param string $book Book abbreviation.
     * @return integer|false Book id.
     */
    private function getBook( $abbr )
    {
        return $this->db->createQueryBuilder()
            ->select( 'id' )
            ->from( 'Book' )
            ->where( 'abbreviation = :name' )
            ->setParameter( ':name', $abbr, 'string' )
            ->execute()
            ->fetchColumn()
        ;
    }

    /**
     * Save the new entry data to the database.
     * 
     * @param integer $id Entry id.
     * @param array $fields Database fields to update.
     * @return integer Affected rows.
     */
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
