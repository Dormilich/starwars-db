<?php

use Phinx\Seed\AbstractSeed;

class AddNodeTypes extends AbstractSeed
{
    public function run()
    {
        $this->insert( 'NodeType', [
            [
                'name' => 'Ability',
            ], [
                'name' => 'Class',
            ], [
                'name' => 'Descriptor', // categories of force powers
            ], [
                'name' => 'Feat',
            ], [
                'name' => 'Force Power',
            ], [
                'name' => 'Force Secret',
            ], [
                'name' => 'Force Talent',
            ], [
                'name' => 'Force Technique',
            ], [
                'name' => 'Language',
            ], [
                'name' => 'Medical Secret',
            ], [
                'name' => 'Race', // sub species, needed for droids
            ], [
                'name' => 'Size',
            ], [
                'name' => 'Skill',
            ], [
                'name' => 'Species',
            ], [
                'name' => 'Talent',
            ], [
                'name' => 'Tradition', // sub class for force users
            ], [
                'name' => 'Training',
            ], [
                'name' => 'Trait',
            ], [
                'name' => 'Tree',
            ], [
                'name' => 'Other',
            ], [
                'name' => 'Weapon',
            ], [
                'name' => 'Weapon Group',
            ], [
                'name' => 'Weapon Type',
            ]
        ] );
    }
}
