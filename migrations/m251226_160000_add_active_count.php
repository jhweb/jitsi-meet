<?php

use yii\db\Migration;

class m251226_160000_add_active_count extends Migration
{
    public function up()
    {
        $this->addColumn('jitsi_live_stream', 'active_count', $this->integer()->defaultValue(0));
    }

    public function down()
    {
        $this->dropColumn('jitsi_live_stream', 'active_count');
    }
}
