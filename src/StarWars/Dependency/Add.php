<?php

namespace StarWars\Dependency;

use Exception;
use RuntimeException;
use UnexpectedValueException;
use StarWars\Entry;
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
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the entry'
            )
            ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 
                'Type of the entry'
            )
            ->addOption( 'depends', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 
                'Dependency of the entry in compact form (<type>:<name>). '.
                'If an entry has a one-of-several dependency, use this option multiple times.'
            )
            ->addOption( 'limit', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 
                'Minimum value for the n-th dependency. Cannot be combined with the `amount` option.'
            )
            ->addOption( 'amount', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 
                'The minimum amount of items from the collection when the n-th dependency is a tree. '.
                'Cannot be combined with the `limit` option.'
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
            $type = $input->getOption( 'type' );

            $id = $this->getEntry( $type, $name );

            if ( ! $id ) {
                $this->io->note( 'There is no such entry in the database' );
                return 0;
            }

            $deps = $input->getOption( 'depends' );
            $count = count( $deps );

            if ( $count === 0 ) {
                $this->io->note( 'There are no dependencies to add.' );
                return 0;
            }

            $deps = $this->getDependencies( $deps );

            $ids = array_pad([], $count, $id );

            $vals = $input->getOption( 'limit' );
            $vals = array_pad( $vals, $count, null );

            $amts = $input->getOption( 'amount' );
            $amts = array_pad( $amts, $count, null );

            $group = $count > 1 
                ? $this->getNextDependencyGroup( $id ) 
                : null
            ;
            $grps = array_pad( [], $count, $group );

            $data = array_map( null, $ids, $deps, $vals, $amts, $grps );

            array_walk( $data, [ $this, 'saveDependency' ] );
        }
        catch ( Exception $e ) {
            $this->io->error( $e->getMessage() );
            if ( $output->isVerbose() ) {
                $this->io->writeln( $e->getTraceAsString() );
            }
            return 1;
        }

        return 0;
    }

    /**
     * Convert named dependencies into database insert sets.
     * 
     * @param array $deps Entry names.
     * @return array Insert sets.
     */
    private function getDependencies( array $deps )
    {
        return array_map( function ( $value ) {
            if ( strpos( $value, ':') ) {
                list( $type, $name ) = explode( ':', $value );
                $id = $this->getEntry( $type, $name );
            }
            else {
                $id = $this->getEntryByName( $value );
            }

            if ( $id > 0 ) {
                return $id;
            }

            $msg = ucwords( $name ) . ' not found.';
            throw new UnexpectedValueException( $msg );
        }, $deps );
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
            ->select( 'max(group_id)' )
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
    private function saveDependency( array $row )
    {
        $keys = [ 'node', 'depends', 'min_value', 'min_count', 'group_id' ];
        $data = array_combine( $keys, $row );
        $data = array_filter( $data );

        $ok = $this->db->insert( 'Dependency', $data, [
            'node' => 'integer',
            'depends' => 'integer',
            'min_value' => 'integer',
            'min_count' => 'integer',
            'group_id' => 'integer',
        ] );

        if ( ! $ok ) {
            throw new RuntimeException( 'Failed to add dependency.' );
        }
    }
}
