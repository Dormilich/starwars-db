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

class Delete extends Command
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
            ->addOption( 'dep', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 
                'Dependency of the entry in compact form (<type>:<name>).'
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

        } catch ( Exception $e ) {
            $this->io->error( $e->getMessage() );
            if ( $output->isVerbose() ) {
                $this->io->writeln( $e->getTraceAsString() );
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
