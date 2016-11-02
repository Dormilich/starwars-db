<?php

use Phinx\Migration\AbstractMigration;

class Collection extends AbstractMigration
{
    public function change()
    {
        $this->table( 'Collection' )
            ->addColumn( 'tree', 'integer' )
            ->addColumn( 'leaf', 'integer' )
            ->addForeignKey( 'tree', 'Node', 'id' )
            ->addForeignKey( 'leaf', 'Node', 'id' )
            ->addIndex( [ 'tree', 'leaf' ], [
                'unique' => true,
            ] )
            ->create()
        ;
    }
}
