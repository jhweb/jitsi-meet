<?php

use yii\db\Migration;

/**
 * Migration: Add scheduling fields to jitsi_live_stream table
 * 
 * Adds columns for scheduled events, calendar integration, and recurrence support.
 * Preserves existing status values: 0=Ended, 1=Live. Adds 2=Scheduled.
 */
class m260106_131300_add_scheduling_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('jitsi_live_stream', 'scheduled_start', $this->dateTime()->null());
        $this->addColumn('jitsi_live_stream', 'scheduled_end', $this->dateTime()->null());
        $this->addColumn('jitsi_live_stream', 'all_day', $this->tinyInteger(1)->defaultValue(0));
        $this->addColumn('jitsi_live_stream', 'rrule', $this->string(255)->null());
        $this->addColumn('jitsi_live_stream', 'exdate', $this->text()->null());
        $this->addColumn('jitsi_live_stream', 'parent_event_id', $this->integer()->null());
        $this->addColumn('jitsi_live_stream', 'recurrence_id', $this->string(50)->null());
        $this->addColumn('jitsi_live_stream', 'uid', $this->string(255)->null());
        $this->addColumn('jitsi_live_stream', 'description', $this->text()->null());
        $this->addColumn('jitsi_live_stream', 'timezone', $this->string(50)->defaultValue('UTC'));

        // Add index for efficient queries
        $this->createIndex(
            'idx-jitsi_live_stream-scheduled_start',
            'jitsi_live_stream',
            'scheduled_start'
        );
        
        $this->createIndex(
            'idx-jitsi_live_stream-parent_event_id',
            'jitsi_live_stream',
            'parent_event_id'
        );

        // Add foreign key for recurrence parent
        $this->addForeignKey(
            'fk-jitsi_live_stream-parent_event',
            'jitsi_live_stream',
            'parent_event_id',
            'jitsi_live_stream',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-jitsi_live_stream-parent_event', 'jitsi_live_stream');
        $this->dropIndex('idx-jitsi_live_stream-parent_event_id', 'jitsi_live_stream');
        $this->dropIndex('idx-jitsi_live_stream-scheduled_start', 'jitsi_live_stream');
        
        $this->dropColumn('jitsi_live_stream', 'timezone');
        $this->dropColumn('jitsi_live_stream', 'description');
        $this->dropColumn('jitsi_live_stream', 'uid');
        $this->dropColumn('jitsi_live_stream', 'recurrence_id');
        $this->dropColumn('jitsi_live_stream', 'parent_event_id');
        $this->dropColumn('jitsi_live_stream', 'exdate');
        $this->dropColumn('jitsi_live_stream', 'rrule');
        $this->dropColumn('jitsi_live_stream', 'all_day');
        $this->dropColumn('jitsi_live_stream', 'scheduled_end');
        $this->dropColumn('jitsi_live_stream', 'scheduled_start');
    }
}
