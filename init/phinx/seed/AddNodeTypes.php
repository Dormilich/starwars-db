<?php

use Phinx\Seed\AbstractSeed;

class AddNodeTypes extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $this->insert( 'NodeType', [
            [
                'name' => 'Ability',
            ], [
                'name' => 'Skill',
            ], [
                'name' => 'Talent',
            ], [
                'name' => 'Feat',
            ], [
                'name' => 'Species',
            ], [
                'name' => 'Class',
            ], [
                'name' => 'Quirk',
            ], [
                'name' => 'Power',
            ], [
                'name' => 'Technique',
            ], [
                'name' => 'Secret',
            ], [
                'name' => 'Action',
            ], [
                'name' => 'Bonus',
            ]
        ] );
    }
}
