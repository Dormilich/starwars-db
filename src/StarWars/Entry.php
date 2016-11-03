<?php

namespace StarWars;

use PDO;
use Exception;
use ErrorException;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Entry extends Command
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
    protected function initialize( InputInterface $input, OutputInterface $output )
    {
        $this->io = new SymfonyStyle( $input, $output );
    }

    /**
     * Get the entry id from the provided input. If a severity level is given, 
     * it throws an error exception, otherwise it return 0.
     * 
     * @param string|null $type Entry type.
     * @param string $name Entry name.
     * @param integer|null $severity The severity level of the `entry not found` error.
     * @return integer Entry id.
     * @throws ErrorException Multiple entries found.
     */
    protected function entry( $type, $name, $severity = null )
    {
        $id = $type 
            ? $this->entryByKey( $type, $name ) 
            : $this->entryByName( $name )
        ;

        if ( $id === 0 and is_int( $severity ) ) {
            $msg = 'There is no such entry in the database.';
            throw new ErrorException( $msg, 0, $severity );
        }

        return $id;
    }

    /**
     * Get the entry id from type & name. Returns `0` if there is no match.
     * 
     * @param string $type Entry type.
     * @param string $name Entry name.
     * @return integer Entry id.
     */
    private function entryByKey( $type, $name )
    {
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
     * Get the entry id only from the name. Returns `0` if there is no match and 
     * throws an exception if there is more than one.
     * 
     * @param string $name Entry name.
     * @return integer Entry id.
     * @throws ErrorException Multiple entries found.
     */
    private function entryByName( $name )
    {
        $entries = $this->getEntries( $name );
        $count = count( $entries );

        if ( $count === 0 ) {
            return 0;
        }

        if ( $count === 1 ) {
            return key( $entries );
        }

        $msg = 'Found %d entries (%s) for %s';
        $msg = sprintf( $msg, $count, implode( ', ', $entries), ucwords( $name ) );
        throw new ErrorException( $msg, 0, 1 );
    }

    /**
     * Get all entries with the same name.
     * 
     * @param string $name Entry name.
     * @return array
     */
    private function getEntries( $name )
    {
        return $this->db->createQueryBuilder()
            ->select( 'n.id', 't.name' )
            ->from( 'Node', 'n' )
            ->innerJoin( 'n', 'NodeType', 't', 'n.type = t.id' )
            ->andWhere( 'n.name LIKE :name' )
            ->setParameter( ':name', $name, 'string' )
            ->execute()
            ->fetchAll( PDO::FETCH_KEY_PAIR )
        ;
    }

    /**
     * Get the node object for the given entry.
     * 
     * @param integer $id Entry id.
     * @return Node
     */
    protected function node( $id )
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

    /**
     * Convert named dependencies into database ids. Skips any names that cannot 
     * be identified uniquely.
     * 
     * @param array $names Entry names.
     * @return array Entry IDs.
     */
    protected function entryList( array $names )
    {
        $parts = array_map( function ( $value ) {
            $parts = explode( ':', $value, 2 );
            if ( count( $parts ) === 1 ) {
                array_unshift( $parts, false );
            }
            return $parts;
        }, $names );

        // for some reason array_map() does not like exceptions from called methods
        $ids = array_map( function ( array $list ) {
            try {
                list( $type, $name ) = $list;
                return $this->entry( $type, $name );
            } catch ( Exception $e ) {
                $this->printError( $e );
                return false;
            }
        }, $parts );

        $ids = array_filter( $ids );

        return $ids;
    }

    /**
     * Print the error message to stdout.
     * 
     * @param Exception $e 
     * @return void
     */
    protected function printError( Exception $e )
    {
        $method = ['note', 'caution', 'warning', 'error'];
        $severity = 3;

        if ( $e instanceof ErrorException ) {
            $severity = min( 3, $e->getSeverity() );
        }

        call_user_func( [$this->io, $method[ $severity ]], $e->getMessage() );

        if ( $this->io->isVerbose() ) {
            $this->io->writeln( $e->getTraceAsString() );
        }
    }
}
