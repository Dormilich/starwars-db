<?php

namespace StarWars\Node;

use ErrorException;
use Doctrine\DBAL\Query\QueryBuilder;
use StarWars\Entry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Info extends Entry
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName(
                'entry:info'
            )
            ->setDescription(
                'Get entry data from the database'
            )
            ->setHelp(
                'An entry is the basic information item in Star Wars Saga Edition. '.
                'It can be a Skill, Feat, Talent, Ability, etc.'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the entry'
            )
            ->addOption( 'type', 't', InputOption::VALUE_REQUIRED, 
                'Type of the entry'
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
                throw new ErrorException( 'There is no such entry in the database', 0, 0 );
            }

            $result = $this->getResult( $id );
            $this->renderResult( $result );
        }
        catch ( Exception $e ) {
            $this->printError( $e );
        }

        return 0;
    }

    /**
     * Get the updated entry’s data.
     * 
     * @param integer $id Entry id.
     * @return array
     */
    private function getResult( $id )
    {
        return $this->db->createQueryBuilder()
            ->select( [
                'n.name',
                't.name AS type',
                'n.description',
                'b.short AS book',
                'n.page',
            ] )
            ->from( 'Node', 'n' )
            ->innerJoin( 'n', 'Book', 'b', 'n.book = b.id' )
            ->innerJoin( 'n', 'NodeType', 't', 'n.type = t.id' )
            ->where( 'n.id = :id' )
            ->setParameter( ':id', $id, 'integer' )
            ->execute()
            ->fetch()
        ;
    }

    /**
     * Display the results of the query object.
     * 
     * @param array $result Entry data.
     * @return void
     */
    private function renderResult( array $result )
    {
        $this->io->section( sprintf( '%s (%s, %s %d)', $result[ 'name' ], 
            $result[ 'type' ], $result[ 'book' ], $result[ 'page' ] ) );

        $this->io->text( $result[ 'description' ] );
    }
}
