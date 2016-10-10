<?php

use Phinx\Migration\AbstractMigration;

class CreateNode extends AbstractMigration
{
    public function change()
    {
        $this->table( 'Node' )
            ->addColumn( 'name', 'text' )
            ->addColumn( 'type', 'integer' )
            ->addColumn( 'description', 'text', [
                'null' => true,
            ] )
            ->addColumn( 'book', 'integer' )
            ->addColumn( 'page', 'integer' )
            ->addForeignKey( 'type', 'NodeType', 'id' )
            ->addForeignKey( 'book', 'Book', 'id' )
            ->addIndex( [ 'type', 'name' ], [
                'unique' => true,
            ] )
            ->create()
        ;
    }
}
