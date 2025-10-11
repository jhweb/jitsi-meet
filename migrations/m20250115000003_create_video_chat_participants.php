<?php

use yii\db\Migration;

/**
 * Handles the creation of table `jitsi_video_chat_participant`.
 */
class m20250115000003_create_video_chat_participants extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('jitsi_video_chat_participant', [
            'id' => $this->primaryKey(),
            'calendar_entry_id' => $this->integer()->notNull()->comment('Calendar entry ID'),
            'user_id' => $this->integer()->notNull()->comment('User ID'),
            'notify_me' => $this->boolean()->defaultValue(false)->comment('User wants notifications'),
            'notification_sent_24h' => $this->boolean()->defaultValue(false)->comment('24h reminder sent'),
            'notification_sent_5min' => $this->boolean()->defaultValue(false)->comment('5min reminder sent'),
            'created_at' => $this->dateTime()->notNull()->comment('Record creation time'),
        ]);

        // Add unique index for calendar_entry_id + user_id combination
        $this->createIndex('idx_jitsi_video_chat_participant_unique', 'jitsi_video_chat_participant', ['calendar_entry_id', 'user_id'], true);

        // Add foreign key constraints
        $this->addForeignKey(
            'fk_jitsi_video_chat_participant_entry_id',
            'jitsi_video_chat_participant',
            'calendar_entry_id',
            'calendar_entry',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_jitsi_video_chat_participant_user_id',
            'jitsi_video_chat_participant',
            'user_id',
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
        $this->dropForeignKey('fk_jitsi_video_chat_participant_user_id', 'jitsi_video_chat_participant');
        $this->dropForeignKey('fk_jitsi_video_chat_participant_entry_id', 'jitsi_video_chat_participant');
        
        $this->dropTable('jitsi_video_chat_participant');
    }
}

