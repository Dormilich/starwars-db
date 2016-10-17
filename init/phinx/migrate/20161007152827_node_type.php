<?php

use Phinx\Migration\AbstractMigration;

class NodeType extends AbstractMigration
{
    public function change()
    {
        $this->table( 'NodeType' )
            ->addColumn( 'name', 'text' )
            ->addIndex( 'name', [
                'unique' => true,
            ] )
            ->create()
        ;
    }
}
