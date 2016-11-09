<?php

use Phinx\Migration\AbstractMigration;

class Species extends AbstractMigration
{
    public function change()
    {
        $this->table( 'SpeciesMod' )
            ->addColumn( 'str', 'integer', [
                'default' => 0,
            ] )
            ->addColumn( 'dex', 'integer', [
                'default' => 0,
            ] )
            ->addColumn( 'con', 'integer', [
                'default' => 0,
            ] )
            ->addColumn( 'int', 'integer', [
                'default' => 0,
            ] )
            ->addColumn( 'wis', 'integer', [
                'default' => 0,
            ] )
            ->addColumn( 'cha', 'integer', [
                'default' => 0,
            ] )
            ->addColumn( 'reflex', 'integer', [
                'default' => 0,
            ] )
            ->addColumn( 'fortitude', 'integer', [
                'default' => 0,
            ] )
            ->addColumn( 'will', 'integer', [
                'default' => 0,
            ] )
            ->addColumn( 'reroll', 'integer', [
                'null' => true,
                'comment' => 'skill that may be rerolled',
            ] )
            ->addColumn( 'focus', 'integer', [
                'null' => true,
                'comment' => 'conditional bonus feat (skill focus) for skill',
            ] )
            ->addColumn( 'feat', 'integer', [
                'null' => true,
                'comment' => 'bonus feat for species',
            ] )
            ->addColumn( 'speed', 'integer', [
                'default' => 6,
                'comment' => 'the species’ base speed',
            ] )
            ->addForeignKey( 'id', 'Node', 'id' )
            ->addForeignKey( 'reroll', 'Node', 'id' )
            ->addForeignKey( 'focus', 'Node', 'id' )
            ->addForeignKey( 'feat', 'Node', 'id' )
            ->create()
        ;

        $this->table( 'SpeciesTrait' )
            ->addColumn( 'species', 'integer' )
            ->addColumn( 'trait', 'integer' )
            ->addIndex( [ 'species', 'trait' ], [
                'unique' => true,
            ] )
            ->addForeignKey( 'species', 'Node', 'id' )
            ->addForeignKey( 'trait', 'Node', 'id' )
            ->create()
        ;
    }
}
