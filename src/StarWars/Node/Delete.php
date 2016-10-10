<?php

namespace StarWars\Node;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Delete extends Command
{
    protected $db;

    public function __construct( Connection $db )
    {
        $this->db = $db;

        parent::__construct();
    }

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

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $io = new SymfonyStyle( $input, $output );
        $id = $this->getEntry( $input );

        if ( ! $id ) {
            $io->note( 'There is no such entry in the database' );
            exit( 0 );
        }

        $this->db->delete( 'Node', [ 'id' => $id ], [ 'integer' ] );
    }

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
}
