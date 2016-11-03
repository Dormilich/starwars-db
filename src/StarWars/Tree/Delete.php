<?php

namespace StarWars\Tree;

use Exception;
use ErrorException;
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
                'tree:delete'
            )
            ->setDescription(
                'Remove one, multiple, or all items of a collection'
            )
            ->addArgument( 'type', InputArgument::REQUIRED, 
                'Name of the collection’s type'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the collection'
            )
            ->addOption( 'item', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 
                'Item of the collection in compact form (<type>:<name>).'
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

            $id = $this->entry( $type, $name, 0 );

            $items = $input->getOption( 'item' );
            $items = $this->entryList( $items );

            $count = $this->deleteItems( $id, $items );
            $output->writeln('<info>Removed '.$count.' members.<info>');
        }
        catch ( Exception $e ) {
            $this->printError( $e );
            return 1;
        }

        return 0;
    }

    /**
     * Delete all or some items from the collection.
     * 
     * @param integer $id Collection entry id.
     * @param array $items Collection member ids.
     * @return integer Number of deleted members.
     */
    private function deleteItems( $id, array $items )
    {
        $query = $this->db->createQueryBuilder()
            ->delete( 'Collection' )
            ->where( 'tree = :id' )
            ->setParameter( ':id', $id, 'integer' )
        ;

        if ( count( $items ) > 0 ) {
            $query
                ->andWhere( 'leaf IN(:item)' )
                ->setParameter( ':item', $items, Connection::PARAM_INT_ARRAY )
            ;
        }

        return $query->execute();
    }
}
