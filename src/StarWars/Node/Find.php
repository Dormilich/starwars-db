<?php

namespace StarWars\Node;

use Doctrine\DBAL\Query\QueryBuilder;
use StarWars\Entry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Find extends Entry
{
    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $name = $input->getArgument( 'name' );
        $type = $input->getOption( 'type' );
        $type = $this->getType( $type );

        $query = $this->getQuery( $type, $name );

        if ( $input->getOption( 'descr' ) ) {
            $query->orWhere( 'n.description LIKE :name' );
        }

        $this->renderResult( $query );

        return 0;
    }

    /**
     * Get the type id from the type name. Returns `0` if there is no such type.
     * 
     * @param string $type Entry type name.
     * @return integer Entry type id.
     */
    private function getType( $type )
    {
        return (int) $this->db->createQueryBuilder()
            ->select( 'id' )
            ->from( 'NodeType' )
            ->where( 'name LIKE :name' )
            ->setParameter( ':name', $type, 'string' )
            ->execute()
            ->fetchColumn()
        ;
    }

    /**
     * Fetch the data matching the input.
     * 
     * @param string $type Entry type name.
     * @param string $name Entry name (or a part thereof).
     * @return QueryBuilder
     */
    private function getQuery( $type, $name )
    {
        return $this->db->createQueryBuilder()
            ->select( [
                'n.name',
                't.name AS type',
                'b.short',
                'n.page',
            ] )
            ->from(
                'Node', 'n'
            )
            ->innerJoin( 'n', 'Book', 'b', 
                'n.book = b.id'
            )
            ->innerJoin( 'n', 'NodeType', 't', 
                'n.type = t.id' . ( $type ? ' AND t.id = ' . $type : '' )
            )
            ->where( 'n.name LIKE :name' )
            ->orderBy( 'b.id', 'asc' )
            ->addOrderBy( 'n.page', 'asc' )
            ->setParameter( ':name', '%'.$name.'%', 'string' )
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
        $result = $query->execute()->fetchAll();

        if ( count( $result ) > 0 ) {
            $this->io->table( [ 'Name', 'Type', 'Book', 'Page' ], $result );
        }
        else {
            $this->io->note( 'Sorry, no matching entries found.' );
        }
    }
}
