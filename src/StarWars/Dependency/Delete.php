<?php

namespace StarWars\Dependency;

use Exception;
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
        try {
            $name = $input->getArgument( 'name' );
            $type = $input->getArgument( 'type' );

            $id = $this->entry( $type, $name, 1 );

            $items = $input->getOption( 'item' );
            $items = $this->entryList( $items );

            $count = $this->deleteItems( $id, $items );
            $output->writeln('<info>Removed '.$count.' dependencies.<info>');
        }
        catch ( Exception $e ) {
            $this->printError( $e );
            return 1;
        }

        return 0;
    }

    /**
     * Delete all or some dependencies from an entry.
     * 
     * @param integer $id Entry id.
     * @param array $items Dependencies’ ids.
     * @return integer Number of deleted dependencies.
     */
    private function deleteItems( $id, array $items )
    {
        $query = $this->db->createQueryBuilder()
            ->delete( 'Dependency' )
            ->where( 'node = :id' )
            ->setParameter( ':id', $id, 'integer' )
        ;

        if ( count( $items ) > 0 ) {
            $query
                ->andWhere( 'depends IN(:item)' )
                ->setParameter( ':item', $items, Connection::PARAM_INT_ARRAY )
            ;
        }

        return $query->execute();
    }
}
