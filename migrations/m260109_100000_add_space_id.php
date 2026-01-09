<?php

use yii\db\Migration;

/**
 * Migration: Add space_id column to jitsi_live_stream table
 * Links scheduled streams to a specific Space for future notification features
 */
class m260109_100000_add_space_id extends Migration
{
    public function safeUp()
    {
        $this->addColumn('jitsi_live_stream', 'space_id', $this->integer()->null()->after('creator_id'));
        
        // Add foreign key to space table
        $this->addForeignKey(
            'fk_jitsi_stream_space',
            'jitsi_live_stream',
            'space_id',
            'space',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_jitsi_stream_space', 'jitsi_live_stream');
        $this->dropColumn('jitsi_live_stream', 'space_id');
    }
}
