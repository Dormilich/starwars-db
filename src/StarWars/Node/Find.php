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

class Find extends Command
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
                'entry:find'
            )
            ->setDescription(
                'List entries by name'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the entry to look up'
            )
            ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 
                'Restrict the type of the entry'
            )
            ->addOption( 'descr', 'd', InputOption::VALUE_NONE, 
                'Extend lookup to the entry description'
            )
        ;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $name = $input->getArgument( 'name' );
        $type = $input->getOption( 'type' );

        $io = new SymfonyStyle( $input, $output );
        $query = $this->getQuery( $type, $name );

        if ( $input->getOption( 'descr' ) ) {
            $query->orWhere( 'n.description LIKE :name' );
        }

        $this->renderResult( $io, $query );
    }

    private function getQuery( $type, $name )
    {
        return $this->db->createQueryBuilder()
            ->select( [
                'n.name',
                't.name AS type',
                'b.abbreviation',
                'n.page',
            ] )
            ->from(
                'Node', 'n'
            )
            ->innerJoin( 'n', 'Book', 'b', 
                'n.book = b.id'
            )
            ->innerJoin( 'n', 'NodeType', 't', 
                'n.type = t.id' . ( $type ? ' AND t.name = ' . $type : '' )
            )
            ->where( 'n.name LIKE :name' )
            ->setParameter( ':name', '%'.$name.'%', 'string' )
        ;
    }

    private function renderResult( SymfonyStyle $io, QueryBuilder $query )
    {
        $result = $query->execute()->fetchAll();

        if ( count( $result ) > 0 ) {
            $io->table( [ 'Name', 'Type', 'Book', 'Page' ], $result );
        }
        else {
            $io->note( 'Sorry, no matching entries found.' );
        }
    }
}
