<?php

use Phinx\Migration\AbstractMigration;

class AddBooks extends AbstractMigration
{
    public function up()
    {
        $this->insert( 'Book', [
            [
                'id' => 1,
                'title' => 'Star Wars: Roleplaying Game - Saga Edition Core Rulebook',
                'abbreviation' => 'Core',
                'isbn' => '978-0-7869-4356-2',
                'authors' => 'Christopher Perkins, ‎Owen K. C. Stephens, ‎Rodney Thompson',
            ], [
                'id' => 2,
                'title' => 'Starships of the Galaxy',
                'abbreviation' => 'Starships',
                'isbn' => '978-0-7869-4823-9',
                'authors' => 'Gary Astleford, ‎Owen K. C. Stephens, ‎Rodney Thompson',
            ], [
                'id' => 3,
                'title' => 'Threats of the Galaxy',
                'abbreviation' => 'Threats',
                'isbn' => '978-0-7869-4781-2',
                'authors' => 'Rodney Thompson',
            ], [
                'id' => 4,
                'title' => 'Knights of the Old Republic Campaign Guide',
                'abbreviation' => 'KOTOR',
                'isbn' => '978-0-7869-4923-6',
                'authors' => 'Rodney Thompson',
            ], [
                'id' => 5,
                'title' => 'The Force Unleashed Campaign Guide',
                'abbreviation' => 'Force',
                'isbn' => '978-0-7869-4743-0',
                'authors' => 'Sterling Hershey, ‎Owen K.C. Stephens, ‎Rodney Thompson',
            ], [
                'id' => 6,
                'title' => 'Scum and Villainy',
                'abbreviation' => 'Scum',
                'isbn' => '978-0-7869-5035-5',
                'authors' => 'Gary Astleford, Robert J. Schwalb',
            ], [
                'id' => 7,
                'title' => 'The Clone Wars Campaign Guide',
                'abbreviation' => 'Clone',
                'isbn' => '978-0-7869-4999-1',
                'authors' => 'Rodney Thompson, ‎Patrick Stutzman, ‎J. D. Wiker',
            ], [
                'id' => 8,
                'title' => 'Legacy Era Campaign Guide',
                'abbreviation' => 'Legacy',
                'isbn' => '978-0-7869-5051-5',
                'authors' => 'Rodney Thompson, ‎Sterling Hershey, ‎Gary Astleford',
            ], [
                'id' => 9,
                'title' => 'Jedi Academy Training Manual',
                'abbreviation' => 'Jedi',
                'isbn' => '978-0-7869-5183-3',
                'authors' => 'Rodney Thompson, ‎Eric Cagle, ‎Patrick Stutzman',
            ], [
                'id' => 10,
                'title' => 'Rebellion Era Campaign Guide',
                'abbreviation' => 'Rebellion',
                'isbn' => '978-0-7869-4983-0',
                'authors' => 'Rodney Thompson, Sterling Hershey',
            ], [
                'id' => 11,
                'title' => 'Galaxy at War',
                'abbreviation' => 'War',
                'isbn' => '978-0-7869-5221-2',
                'authors' => 'Rodney Thompson, Gary Astleford',
            ], [
                'id' => 12,
                'title' => 'Scavenger’s Guide to Droids',
                'abbreviation' => 'Droids',
                'isbn' => '978-0-7869-5230-4',
                'authors' => 'Rodney Thompson, Sterling Hershey',
            ], [
                'id' => 13,
                'title' => 'Galaxy of Intrigue',
                'abbreviation' => 'Intrigue',
                'isbn' => '978-0-7869-5400-1',
                'authors' => 'Rodney Thompson, ‎Gary Astleford, ‎Eric Cagle',
            ], [
                'id' => 14,
                'title' => 'The Unknown Regions',
                'abbreviation' => 'Regions',
                'isbn' => '978-0-7869-5399-8',
                'authors' => 'Rodney Thompson, ‎Sterling Hershey, ‎Daniel Wallace',
            ],
        ] );
    }
}
