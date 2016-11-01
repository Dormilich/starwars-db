<?php

use Phinx\Migration\AbstractMigration;

class Dependency extends AbstractMigration
{
    public function change()
    {
        $this->table( 'Dependency' )
            ->addColumn( 'node', 'integer' )
            // requirement for `node`
            ->addColumn( 'depends', 'integer' )
            // limiting value (e.g. DEX 13)
            ->addColumn( 'min_value', 'integer', [
                'null' => true,
            ])
            // min amount of items from a tree
            // e.g. two talents from the Autonomy talent tree
            ->addColumn( 'min_count', 'text', [
                'null' => true,
            ])
            // create an OR relation between entries
            ->addColumn( 'group_id', 'integer', [
                'null' => true,
            ] )
            ->addForeignKey( 'node', 'Node', 'id' )
            ->addForeignKey( 'depends', 'Node', 'id' )
            ->addIndex( [ 'node', 'depends' ], [
                'unique' => true,
            ] )
            ->create()
        ;
    }
}
