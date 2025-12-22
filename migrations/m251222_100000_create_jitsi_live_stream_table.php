<?php

use yii\db\Migration;

class m251222_100000_create_jitsi_live_stream_table extends Migration
{
    public function up()
    {
        $this->createTable('jitsi_live_stream', [
            'id' => $this->primaryKey(),
            'room_name' => $this->string()->notNull(),
            'session_id' => $this->string()->null()->comment('8x8 Session ID'),
            'status' => $this->tinyInteger()->defaultValue(0)->comment('0=ended, 1=live'),
            'start_time' => $this->dateTime(),
            'end_time' => $this->dateTime(),
            'creator_id' => $this->integer()->null(),
            'participant_count' => $this->integer()->defaultValue(0),
            'stream_url' => $this->string()->null()->comment('External stream URL e.g. YouTube'),
            'recording_url' => $this->string()->null()->comment('VOD link'),
            'event_id' => $this->string()->unique()->comment('8x8 idempotency key'),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->createIndex('idx-jitsi-live-stream-room', 'jitsi_live_stream', 'room_name');
        $this->createIndex('idx-jitsi-live-stream-session', 'jitsi_live_stream', 'session_id');
        $this->createIndex('idx-jitsi-live-stream-status', 'jitsi_live_stream', 'status');
    }

    public function down()
    {
        $this->dropTable('jitsi_live_stream');
    }
}
