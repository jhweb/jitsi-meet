<?php

use yii\db\Migration;

/**
 * Handles the creation of table `jitsi_calendar_video_chat`.
 */
class m20250115000002_create_calendar_video_chat_link extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('jitsi_calendar_video_chat', [
            'id' => $this->primaryKey(),
            'calendar_entry_id' => $this->integer()->notNull()->comment('Calendar entry ID'),
            'room_name' => $this->string(255)->notNull()->comment('Generated room name'),
            'custom_notification_message' => $this->text()->null()->comment('Override default notification text'),
            'enable_recording' => $this->boolean()->defaultValue(false)->comment('Enable recording'),
            'enable_streaming' => $this->boolean()->defaultValue(false)->comment('Enable streaming'),
            'youtube_stream_key' => $this->string(255)->null()->comment('YouTube stream key'),
            'created_by' => $this->integer()->notNull()->comment('User who created the video chat'),
            'created_at' => $this->dateTime()->notNull()->comment('Record creation time'),
            'updated_at' => $this->dateTime()->notNull()->comment('Record update time'),
        ]);

        // Add unique index for calendar_entry_id
        $this->createIndex('idx_jitsi_calendar_video_chat_entry_id', 'jitsi_calendar_video_chat', 'calendar_entry_id', true);

        // Add foreign key constraints
        $this->addForeignKey(
            'fk_jitsi_calendar_video_chat_entry_id',
            'jitsi_calendar_video_chat',
            'calendar_entry_id',
            'calendar_entry',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_jitsi_calendar_video_chat_created_by',
            'jitsi_calendar_video_chat',
            'created_by',
            'user',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_jitsi_calendar_video_chat_created_by', 'jitsi_calendar_video_chat');
        $this->dropForeignKey('fk_jitsi_calendar_video_chat_entry_id', 'jitsi_calendar_video_chat');
        
        $this->dropTable('jitsi_calendar_video_chat');
    }
}

