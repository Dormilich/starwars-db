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
            // optional: limiting value (e.g. DEX 13)
            ->addColumn( 'value', 'text', [
                'null' => true,
            ])
            // if there is a 'one of' dependency
            ->addColumn( 'dep_group', 'integer', [
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
