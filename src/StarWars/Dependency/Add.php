<?php

namespace StarWars\Dependency;

use Exception;
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
                'dependency:add'
            )
            ->setDescription(
                'Add a dependency of an entry'
            )
            ->setHelp(
                'An entry is the basic information item in Star Wars Saga Edition. '.
                'It can be a Skill, Feat, Talent, Ability, etc. Often an entry '.
                'requires an existing (set of) entries before it can be taken.'
            )
            ->addArgument( 'type', InputArgument::REQUIRED, 
                'Name of the entry’s type'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the entry'
            )
            ->addOption( 'dep', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 
                'Dependency of the entry in compact form (<type>:<name> [value]). '.
                'If an entry has a one-of-several dependency, use this option multiple times.'
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
        $name = $input->getArgument( 'name' );
        $type = $input->getArgument( 'type' );

        $id = $this->getEntry( $type, $name );

        if ( $id === 0 ) {
            $this->io->note( 'There is no such entry in the database' );
            return 0;
        }

        $deps = $input->getOption( 'dep' );

        if ( count( $deps ) === 0 ) {
            $this->io->note( 'There are no dependencies to add.' );
            return 0;
        }

        try {
            $deps = $this->getDependencies( $id, $deps );

            array_walk( $deps, [ $this, 'saveDependency' ] );

        } catch ( Exception $e ) {
            $this->io->error( $e->getMessage() );
            if ( $output->isVerbose() ) {
                $this->io->listing( $e->getTrace() );
            }
            return 1;
        }

        return 0;
    }

    /**
     * Get the entry id from the provided input. Returns `0` if there is no match.
     * 
     * @param string $type Entry type.
     * @param string $name Entry name.
     * @return integer Entry id.
     */
    private function getEntry( $type, $name )
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
     * Convert named dependencies into database insert sets.
     * 
     * @param array $deps Entry names.
     * @return array Insert sets.
     */
    private function getDependencies( $entry, array $deps )
    {
        array_walk( $deps, function ( &$value, $key, $entry ) {
            if ( preg_match( '/^(\w+):(.+)( \[(.+)\])?$/', $value, $match ) === 0 ) {
                throw new UnexpectedValueException( 'Invalid entry format for ' . $value );
            }

            $id = $this->getEntry( $match[ 1 ], $match[ 2 ] );

            if ( $id === 0 ) {
                $msg = ucfirst( $match[ 1 ] ) . ' ' . $match[ 2 ] . ' not found.';
                throw new UnexpectedValueException( $msg );
            }

            $value = [
                'node' => $entry,
                'depends' => $id,
            ];
            if ( isset( $match[ 4 ] ) ) {
                $value[ 'value' ] = $match[ 4 ];
            }
        }, $entry );

        if ( count( $deps ) > 1 ) {
            array_walk( $deps, function ( &$value, $key, $group ) {
                $value[ 'dep_group' ] = $group;
            }, $this->getNextDependencyGroup( $entry ) );
        }

        return  $deps;
    }

    /**
     * Get the next group id if the input data are a set of entries.
     * 
     * @param integer $entry Entry id.
     * @return integer New dependency group number.
     */
    private function getNextDependencyGroup( $entry )
    {
        return 1 + (int) $this->db->createQueryBuilder()
            ->select( 'max(dep_group)' )
            ->from( 'Dependency' )
            ->where( 'node = ?' )
            ->groupBy( 'node' )
            ->setParameter( 0, $entry, 'integer' )
            ->execute()
            ->fetchColumn()
        ;
    }

    /**
     * Add dependencies to the database.
     * 
     * @param array $data Insert set.
     * @return integer Primary key of the dependency.
     */
    private function saveDependency( array $data )
    {
        $ok = $this->db->insert( 'Dependency', $data, [
            'node' => 'integer',
            'value' => 'string',
            'depends' => 'integer',
            'dep_group' => 'integer',
        ] );

        if ( $ok ) {
            return $this->db->lastInsertId();
        }

        throw new RuntimeException( 'Failed to add dependency.' );
    }
}
