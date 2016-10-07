<?php

use Phinx\Migration\AbstractMigration;

class CreateNodeType extends AbstractMigration
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
