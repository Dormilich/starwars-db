<?php

namespace StarWars\Modifier;

use Exception;
use ErrorException;
use PDO;
use StarWars\Entry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Species extends Entry
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName(
                'mod:species'
            )
            ->setDescription(
                'Set the ability modifiers and some special bonuses granted by a species'
            )
            ->addArgument( 'name', InputArgument::REQUIRED, 
                'Name of the species'
            )
            ->addOption( 'list', 'l', InputOption::VALUE_NONE, 
                'Only list the currently defined species modifiers'
            )
            ->addOption( 'str', null, InputOption::VALUE_REQUIRED, 
                'The strength modifier', 0
            )
            ->addOption( 'dex', null, InputOption::VALUE_REQUIRED, 
                'The dexterity modifier', 0
            )
            ->addOption( 'con', null, InputOption::VALUE_REQUIRED, 
                'The constitution modifier', 0
            )
            ->addOption( 'int', null, InputOption::VALUE_REQUIRED, 
                'The intelligence modifier', 0
            )
            ->addOption( 'wis', null, InputOption::VALUE_REQUIRED, 
                'The wisdom modifier', 0
            )
            ->addOption( 'cha', null, InputOption::VALUE_REQUIRED, 
                'The charisma modifier', 0
            )
            ->addOption( 'reflex', null, InputOption::VALUE_REQUIRED, 
                'The reflex defense bonus', 0
            )
            ->addOption( 'fortitude', null, InputOption::VALUE_REQUIRED, 
                'The fortitude defense bonus', 0
            )
            ->addOption( 'will', null, InputOption::VALUE_REQUIRED, 
                'The will defense bonus', 0
            )
            ->addOption( 'speed', null, InputOption::VALUE_REQUIRED, 
                'The base speed (squares) of the species', 6
            )
            ->addOption( 'reroll', null, InputOption::VALUE_REQUIRED, 
                'The name of the skill that can be rerolled'
            )
            ->addOption( 'focus', null, InputOption::VALUE_REQUIRED, 
                'The name of the skill that triggers the conditional bonus feat'
            )
            ->addOption( 'feat', null, InputOption::VALUE_REQUIRED, 
                'The name of the bonus feat granted by the species'
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
            $id = $this->entry( 'Species', $name, 0 );

            $this->addSpecies( $id );

            if ( $input->getOption( 'list' ) ) {
                $this->showModifiers( $id );
                return 0;
            }

            $mod = $this->getModifiers( $input );
            $this->saveModifier( $id, $mod );

            $msg = '<info>Modifiers saved for species ' . ucfirst( $name ) . '</info>';
            $output->writeln( $msg );
        }
        catch ( Exception $e ) {
            $this->printError( $e );
            return 1;
        }

        return 0;
    }

    /**
     * Fetch the modifiers from the input and validate them.
     * 
     * @param InputInterface $input 
     * @return array
     * @throws ErrorException Invalid Skill name.
     */
    private function getModifiers( InputInterface $input )
    {
        $mod = [ 'reroll' => null, 'focus'  => null, 'feat' => null ];

        if ( $reroll = $input->getOption( 'reroll' ) ) {
            $mod[ 'reroll' ] = $this->entry( 'Skill', $reroll, 0 );
        }
        if ( $focus = $input->getOption( 'focus' ) ) {
            $mod[ 'focus' ] = $this->entry( 'Skill', $focus, 0 );
        }
        if ( $feat = $input->getOption( 'feat' ) ) {
            $mod[ 'feat' ] = $this->entry( 'Feat', $feat, 0 );
        }
        // speed cannot be negative
        $mod[ 'speed' ] = $this->filterInt( $input, 'speed', 6, 0 );
        // modifiers with a default value of `0`
        $keys = [ 'str', 'dex', 'con', 'int', 'wis', 'cha', 'reflex', 'fortitude', 'will' ];
        foreach ( $keys as $key ) {
            $mod[ $key ] = $this->filterInt( $input, $key, 0 );
        }

        return $mod;
    }

    /**
     * Save the modifiers in the database.
     * 
     * @param integer $id Species entry id.
     * @param array $data Modifiers.
     * @return integer Affected rows.
     */
    private function saveModifier( $id, array $data )
    {
        $int = $this->filterType( $data, 'is_int', 'integer' );
        $null = $this->filterType( $data, 'is_null', PDO::PARAM_NULL );
        $types = $int + $null;
        $types[ 'id' ] = 'integer';

        return $this->db->update( 'SpeciesMod', $data, [ 'id' => $id ], $types );
    }

    /**
     * Validate an input value as integer.
     * 
     * @param InputInterface $input 
     * @param string $name Option name.
     * @param integer $default Default value if validation fails.
     * @param integer|null $min Optional minimum value.
     * @return integer Validated input value.
     */
    private function filterInt( InputInterface $input, $name, $default, $min = null )
    {
        $options = [ 'default' => $default ];
        if ( is_int( $min ) ) {
            $options[ 'min_range' ] = $min;
        }

        $value = $input->getOption( $name );
        $data = filter_var( $value, \FILTER_VALIDATE_INT, [ 'options' => $options ] );

        return $data;
    }

    /**
     * Determine the database type based on the data types. Returns an array 
     * with the database fields and their according types.
     * 
     * @param array $data Database values.
     * @param callable $typeTest Test function.
     * @param string|integer $typeValue DBAL type string or PDO::PARAM_* constant.
     * @return array
     */
    private function filterType( array $data, callable $typeTest, $typeValue )
    {
        $matches = array_filter( $data, $typeTest );
        $keys = array_keys( $matches );
        $values = array_fill(0, count( $keys ), $typeValue );
        $types = array_combine( $keys, $values );

        return $types;
    }

    /**
     * Add a species entry with the default values. Otherwise a switch between 
     * insert/update would be necessary.
     * 
     * @param integer $id Species entry id.
     * @return boolean If an insert was executed.
     */
    private function addSpecies( $id )
    {
        if ( ! $this->speciesExists( $id ) ) {
            $this->db->insert( 'SpeciesMod', [ 'id' => $id ], [ 'integer' ] );
            return true;
        }
        return false;
    }

    /**
     * Query the Species table for a species entry.
     * 
     * @param integer $id Species entry id.
     * @return boolean True if an entry exists, false otherwise.
     */
    private function speciesExists( $id )
    {
        return (bool) $this->db->createQueryBuilder()
            ->select( '1' )
            ->from( 'SpeciesMod' )
            ->where( 'id = ?' )
            ->setParameter( 0, $id, 'integer' )
            ->execute()
            ->fetchColumn()
        ;
    }

    private function showModifiers( $id )
    {
        $data = $this->fetchSpeciesMod( $id );

        $this->io->section( $data[ 'species' ] . ' species modifiers' );
        $this->io->writeln( 'Base speed: ' . $data[ 'speed' ] . ' squares' );
        $this->io->newLine();

        $this->showAbilities( $data );
        $this->showDefenses( $data );

        if ( $data[ 'feat' ] ) {
            $this->io->writeln( 'Bonus Feat: ' . $data[ 'feat' ] );
            $this->io->newLine();
        }

        if ( $data[ 'reroll' ] ) {
            $line = $data[ 'species' ] . 's may choose to reroll any ' . $data[ 'reroll' ] 
                . ' check, but the result of the reroll must be accepted even if it is worse.';
            $this->io->writeln( $line );
            $this->io->newLine();
        }

        if ( $data[ 'focus' ] ) {
            $line = 'A %1$s with %2$s as trained skill gains Skill Focus (%2$s) as bonus feat.';
            $this->io->writeln( sprintf( $line, $data[ 'species' ], $data[ 'focus' ] ) );
            $this->io->newLine();
        }
    }

    private function fetchSpeciesMod( $id )
    {
        return $this->db->createQueryBuilder()
            ->select( [
                'n0.name AS species',
                's.str',
                's.dex',
                's.con',
                's.int',
                's.wis',
                's.cha',
                's.speed',
                's.reflex',
                's.fortitude',
                's.will',
                'n1.name AS reroll',
                'n2.name AS focus',
                'n3.name AS feat',
            ] )
            ->from( 'SpeciesMod', 's' )
            ->leftJoin( 's', 'Node', 'n0', 's.id = n0.id')
            ->leftJoin( 's', 'Node', 'n1', 's.reroll = n1.id')
            ->leftJoin( 's', 'Node', 'n2', 's.focus = n2.id')
            ->leftJoin( 's', 'Node', 'n3', 's.feat = n3.id')
            ->where( 's.id = ?' )
            ->setParameter( 0, $id, 'integer' )
            ->execute()
            ->fetch()
        ;
    }

    private function showAbilities( array $data )
    {
        $abilities = [ 'str', 'dex', 'con', 'int', 'wis', 'cha' ];
        $data = array_intersect_key( $data, array_flip( $abilities ) );
        $data = array_filter( $data );

        if ( count( $data ) === 0 ) {
            return;
        }

        $this->io->write( 'Abilities:' );
        foreach ($data as $key => $value) {
            $text = sprintf( ' %s %+d ', strtoupper( $key ), $value );
            $this->io->write( $text );
        }
        $this->io->newLine( 2 );
    }

    private function showDefenses( array $data )
    {
        $defense = [ 'reflex', 'fortitude', 'will' ];
        $data = array_intersect_key( $data, array_flip( $defense ) );
        $data = array_filter( $data );

        if ( count( $data ) === 0 ) {
            return;
        }

        $this->io->write( 'Defenses:' );
        foreach ($data as $key => $value) {
            $text = sprintf( ' %s Defense %+d ', ucfirst( $key ), $value );
            $this->io->write( $text );
        }
        $this->io->newLine( 2 );
    }
}
