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
                'name' => 'Bonus',
            ], [
                'name' => 'Class',
            ], [
                'name' => 'Feat',
            ], [
                'name' => 'Power',
            ], [
                'name' => 'Secret',
            ], [
                'name' => 'Skill',
            ], [
                'name' => 'Species',
            ], [
                'name' => 'Talent',
            ], [
                'name' => 'Technique',
            ], [
                'name' => 'Tree',
            ]
        ] );
    }
}
