<?php

namespace StarWars\Node;

use StarWars\Entry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
                'entry:delete'
            )
            ->setDescription(
                'Delete an entry from the database'
            )
            ->addArgument( 'type', InputArgument::REQUIRED, 
                'Name of the entry’s type'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the entry'
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

        $id = $this->entry( $type, $name );

        if ( $id > 0 ) {
            $this->db->delete( 'Node', [ 'id' => $id ], [ 'integer' ] );
        }
        else {
            $this->io->note( 'There is no such entry in the database' );
        }

        return 0;
    }
}
