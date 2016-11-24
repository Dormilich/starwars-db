<?php

use Phinx\Migration\AbstractMigration;

class SpeciesMod extends AbstractMigration
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
            ->addColumn( 'size', 'integer', [
                'comment' => 'the species’ size, usually `small` or `medium`',
            ] )
            ->addForeignKey( 'id', 'Node', 'id' )
            ->addForeignKey( 'focus', 'Node', 'id' )
            ->addForeignKey( 'feat', 'Node', 'id' )
            ->addForeignKey( 'size', 'Node', 'id' )
            ->create()
        ;
    }
}
