<?php

namespace StarWars\Dependency;

use Exception;
use RuntimeException;
use UnexpectedValueException;
use Doctrine\DBAL\Connection;
use StarWars\Entry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends Entry
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName(
                'dependency:delete'
            )
            ->setDescription(
                'Remove one, multiple, or all dependencies of an entry'
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
            ->addOption( 'item', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 
                'Dependency of the entry in compact form (<type>:<name>).'
            )
        ;
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

        $deps = $input->getOption( 'item' );

        try {
            $query = $this->db->createQueryBuilder()
                ->delete( 'Dependency' )
                ->where( 'node = :id' )
                ->setParameter( ':id', $id, 'integer' )
            ;
            if ( count( $deps ) > 0 ) {
                $deps = $this->getDependencies( $deps );
                $query
                    ->andWhere( 'depends IN(:dep)' )
                    ->setParameter( ':dep', $deps, Connection::PARAM_INT_ARRAY )
                ;
            }
            $count = $query->execute();
            $output->writeln('<info>Removed '.$count.' dependencies.<info>');
        }
        catch ( Exception $e ) {
            $this->printError( $e );
            return 1;
        }

        return 0;
    }

    /**
     * Convert named dependencies into database ids.
     * 
     * @param array $deps Entry names.
     * @return array Entry IDs.
     */
    private function getDependencies( array $deps )
    {
        $deps = array_filter( $deps, function ( $value ) {
            return strpos( $value, ':') > 0;
        });

        $deps = array_map( function ( $value ) {
            list( $type, $name ) = explode( ':', $value, 2 );
            return $this->getEntry( $type, $name );
        }, $deps );

        $deps = array_filter( $deps );

        return  $deps;
    }
}
