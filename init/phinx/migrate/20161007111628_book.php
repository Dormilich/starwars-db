<?php

use Phinx\Migration\AbstractMigration;

class Book extends AbstractMigration
{
    public function change()
    {
        $this->table( 'Book' )
            ->addColumn( 'title', 'text' )
            ->addColumn( 'short', 'text' )
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
