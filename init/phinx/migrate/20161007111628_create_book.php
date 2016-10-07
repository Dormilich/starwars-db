<?php

use Phinx\Migration\AbstractMigration;

class CreateBook extends AbstractMigration
{
    public function change()
    {
        $this->table( 'Book' )
            ->addColumn( 'title', 'text' )
            ->addColumn( 'abbreviation', 'text' )
            ->addColumn( 'isbn', 'text' )
            ->addColumn( 'authors', 'text' )
            ->addIndex( 'title', [
                'unique' => true,
            ] )
            ->addIndex( 'abbreviation', [
                'unique' => true,
            ] )
            ->create()
        ;
    }
}
