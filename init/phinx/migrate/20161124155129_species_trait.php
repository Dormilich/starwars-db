<?php

use Phinx\Migration\AbstractMigration;

class SpeciesTrait extends AbstractMigration
{
    public function change()
    {
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
