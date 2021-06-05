<?php
use Migrations\AbstractMigration;

class ChangeClums3ToSopAreas extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('sop_areas');
        $table->changeColumn('name', 'string', [
            'null' => true,
        ]);
        $table->changeColumn('minpoint', 'integer', [
            'null' => true,
        ]);
        $table->changeColumn('maxpoint', 'integer', [
            'null' => true,
        ]);

        $table->save();
    }
}
