<?php

use yii\db\Migration;

class m251230_164200_add_title_column extends Migration
{
    public function up()
    {
        $this->addColumn('jitsi_live_stream', 'title', $this->string(255)->after('room_name'));
    }

    public function down()
    {
        $this->dropColumn('jitsi_live_stream', 'title');
    }
}
