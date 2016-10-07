<?php

use Phinx\Migration\AbstractMigration;

class AddNodeTypes extends AbstractMigration
{
    public function up()
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
            ]
        ] );
    }
}
