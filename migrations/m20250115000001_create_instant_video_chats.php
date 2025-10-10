<?php

use yii\db\Migration;

/**
 * Handles the creation of table `jitsi_instant_video_chat`.
 */
class m20250115000001_create_instant_video_chats extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('jitsi_instant_video_chat', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->null()->comment('Optional custom title'),
            'description' => $this->text()->null()->comment('Optional custom description'),
            'room_name' => $this->string(255)->notNull()->comment('Generated or custom room name'),
            'created_by' => $this->integer()->notNull()->comment('User who created the chat'),
            'space_id' => $this->integer()->null()->comment('Space ID for space chats'),
            'user_id' => $this->integer()->null()->comment('User ID for 1-on-1 chats'),
            'status' => $this->enum(['active', 'ended'])->defaultValue('active')->comment('Chat status'),
            'participant_count' => $this->integer()->defaultValue(0)->comment('Number of participants'),
            'started_at' => $this->dateTime()->null()->comment('When chat started'),
            'ended_at' => $this->dateTime()->null()->comment('When chat ended'),
            'created_at' => $this->dateTime()->notNull()->comment('Record creation time'),
            'updated_at' => $this->dateTime()->notNull()->comment('Record update time'),
        ]);

        // Add indexes
        $this->createIndex('idx_jitsi_instant_video_chat_space_id', 'jitsi_instant_video_chat', 'space_id');
        $this->createIndex('idx_jitsi_instant_video_chat_status', 'jitsi_instant_video_chat', 'status');
        $this->createIndex('idx_jitsi_instant_video_chat_created_by', 'jitsi_instant_video_chat', 'created_by');
        $this->createIndex('idx_jitsi_instant_video_chat_user_id', 'jitsi_instant_video_chat', 'user_id');

        // Add foreign key constraints
        $this->addForeignKey(
            'fk_jitsi_instant_video_chat_space_id',
            'jitsi_instant_video_chat',
            'space_id',
            'space',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_jitsi_instant_video_chat_created_by',
            'jitsi_instant_video_chat',
            'created_by',
            'user',
            'id',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_jitsi_instant_video_chat_user_id',
            'jitsi_instant_video_chat',
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
        $this->dropForeignKey('fk_jitsi_instant_video_chat_user_id', 'jitsi_instant_video_chat');
        $this->dropForeignKey('fk_jitsi_instant_video_chat_created_by', 'jitsi_instant_video_chat');
        $this->dropForeignKey('fk_jitsi_instant_video_chat_space_id', 'jitsi_instant_video_chat');
        
        $this->dropTable('jitsi_instant_video_chat');
    }
}
