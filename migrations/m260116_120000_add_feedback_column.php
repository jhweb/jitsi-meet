<?php

use yii\db\Migration;

class m260116_120000_add_feedback_column extends Migration
{
    public function up()
    {
        $this->addColumn('jitsi_live_stream', 'feedback', $this->text()->null());
    }

    public function down()
    {
        $this->dropColumn('jitsi_live_stream', 'feedback');
    }
}
