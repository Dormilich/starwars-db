<?php

namespace StarWars\Node;

use Exception;
use UnexpectedValueException;
use Doctrine\DBAL\Query\QueryBuilder;
use StarWars\Entry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Set extends Entry
{
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
        try {
            $name = $input->getArgument( 'name' );
            $type = $input->getArgument( 'type' );

            $id = $this->getEntry( $type, $name );

            if ( ! $id ) {
                $this->io->note( 'There is no such entry in the database' );
                return 0;
            }

            $data = [];
            $data[ 'book' ] = $this->inputBook( $input );
            $data[ 'page' ] = $this->inputPage( $input );
            $data[ 'description' ] = $input->getOption( 'descr' );

            $data = array_filter( $data );
            $this->updateEntry( $id, $data );

            $query = $this->getQuery( $id );
            $this->renderResult( $query );

        } catch ( Exception $e ) {
            $this->io->error( $e->getMessage() );
            if ( $output->isVerbose() ) {
                $this->io->writeln( $e->getTraceAsString() );
            }
            return 1;
        }

        return 0;
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
            ->where( 'abbreviation LIKE :name' )
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
                'b.short AS book',
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
        $result = $query->execute()->fetch();

        $this->io->section( sprintf( '%s (%s, %s %d)', $result[ 'name' ], 
            $result[ 'type' ], $result[ 'book' ], $result[ 'page' ] ) );
        $this->io->text( $result[ 'description' ] );
        $this->io->newLine();
    }
}
