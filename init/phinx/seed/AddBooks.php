<?php

use Phinx\Seed\AbstractSeed;

class AddBooks extends AbstractSeed
{
    public function run()
    {
        $this->insert( 'Book', [
            [
                'title' => 'Star Wars: Roleplaying Game - Saga Edition Core Rulebook',
                'short' => 'Core Rulebook',
                'abbreviation' => 'Core',
                'isbn' => '978-0-7869-4356-2',
                'authors' => 'Christopher Perkins, ‎Owen K. C. Stephens, ‎Rodney Thompson',
            ], [
                'title' => 'Starships of the Galaxy',
                'short' => 'Starships of the Galaxy',
                'abbreviation' => 'Starships',
                'isbn' => '978-0-7869-4823-9',
                'authors' => 'Gary Astleford, ‎Owen K. C. Stephens, ‎Rodney Thompson',
            ], [
                'title' => 'Threats of the Galaxy',
                'short' => 'Threats of the Galaxy',
                'abbreviation' => 'Threats',
                'isbn' => '978-0-7869-4781-2',
                'authors' => 'Rodney Thompson',
            ], [
                'title' => 'Knights of the Old Republic Campaign Guide',
                'short' => 'Knights of the Old Republic',
                'abbreviation' => 'KOTOR',
                'isbn' => '978-0-7869-4923-6',
                'authors' => 'Rodney Thompson',
            ], [
                'title' => 'The Force Unleashed Campaign Guide',
                'short' => 'Force Unleashed',
                'abbreviation' => 'Force',
                'isbn' => '978-0-7869-4743-0',
                'authors' => 'Sterling Hershey, ‎Owen K.C. Stephens, ‎Rodney Thompson',
            ], [
                'title' => 'Scum and Villainy',
                'short' => 'Scum and Villainy',
                'abbreviation' => 'Scum',
                'isbn' => '978-0-7869-5035-5',
                'authors' => 'Gary Astleford, Robert J. Schwalb',
            ], [
                'title' => 'The Clone Wars Campaign Guide',
                'short' => 'Clone Wars',
                'abbreviation' => 'Clone',
                'isbn' => '978-0-7869-4999-1',
                'authors' => 'Rodney Thompson, ‎Patrick Stutzman, ‎J. D. Wiker',
            ], [
                'title' => 'Legacy Era Campaign Guide',
                'short' => 'Legacy Era',
                'abbreviation' => 'Legacy',
                'isbn' => '978-0-7869-5051-5',
                'authors' => 'Rodney Thompson, ‎Sterling Hershey, ‎Gary Astleford',
            ], [
                'title' => 'Jedi Academy Training Manual',
                'short' => 'Jedi Academy',
                'abbreviation' => 'Jedi',
                'isbn' => '978-0-7869-5183-3',
                'authors' => 'Rodney Thompson, ‎Eric Cagle, ‎Patrick Stutzman',
            ], [
                'title' => 'Rebellion Era Campaign Guide',
                'short' => 'Rebellion Era',
                'abbreviation' => 'Rebellion',
                'isbn' => '978-0-7869-4983-0',
                'authors' => 'Rodney Thompson, Sterling Hershey',
            ], [
                'title' => 'Galaxy at War',
                'short' => 'Galaxy at War',
                'abbreviation' => 'War',
                'isbn' => '978-0-7869-5221-2',
                'authors' => 'Rodney Thompson, Gary Astleford',
            ], [
                'title' => 'Scavenger’s Guide to Droids',
                'short' => 'Scavenger’s Guide',
                'abbreviation' => 'Droids',
                'isbn' => '978-0-7869-5230-4',
                'authors' => 'Rodney Thompson, Sterling Hershey',
            ], [
                'title' => 'Galaxy of Intrigue',
                'short' => 'Galaxy of Intrigue',
                'abbreviation' => 'Intrigue',
                'isbn' => '978-0-7869-5400-1',
                'authors' => 'Rodney Thompson, ‎Gary Astleford, ‎Eric Cagle',
            ], [
                'title' => 'The Unknown Regions',
                'short' => 'Unknown Regions',
                'abbreviation' => 'Regions',
                'isbn' => '978-0-7869-5399-8',
                'authors' => 'Rodney Thompson, ‎Sterling Hershey, ‎Daniel Wallace',
            ],
        ] );
    }
}
