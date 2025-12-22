<?php

use yii\db\Migration;

class m251222_120000_alter_recording_url_text extends Migration
{
    public function up()
    {
        $this->alterColumn('jitsi_live_stream', 'recording_url', $this->text());
    }

    public function down()
    {
        $this->alterColumn('jitsi_live_stream', 'recording_url', $this->string(255));
    }
}
