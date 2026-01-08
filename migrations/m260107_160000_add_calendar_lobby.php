<?php

use yii\db\Migration;

/**
 * Class m260107_160000_add_calendar_lobby
 */
class m260107_160000_add_calendar_lobby extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('jitsi_live_stream', 'calendar_entry_id', $this->integer()->null());
        $this->addColumn('jitsi_live_stream', 'lobby_enabled', $this->boolean()->defaultValue(0));
        
        // Add foreign key to content table (assuming CalendarEntry is a Content)
        // Note: calendar_entry_id usually points to the 'content' or specific model ID?
        // In HumHub, CalendarEntry has a content record. 
        // We will link to the `calendar_entry` table (module_calendar_entry).
        // Let's check table name for Calendar Entry. Usually `calendar_entry`.
        // To be safe, we just add the column now. We can add FK if sure of table name, 
        // but 'calendar_entry' is standard.
        // Let's skip FK constraint for now to avoid dependency errors if table is missing,
        // or wrap in check.
        
        if ($this->db->getTableSchema('calendar_entry', true) !== null) {
             $this->addForeignKey(
                'fk-jitsi-calendar-entry',
                'jitsi_live_stream',
                'calendar_entry_id',
                'calendar_entry',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->getTableSchema('calendar_entry', true) !== null) {
            $this->dropForeignKey('fk-jitsi-calendar-entry', 'jitsi_live_stream');
        }
        
        $this->dropColumn('jitsi_live_stream', 'lobby_enabled');
        $this->dropColumn('jitsi_live_stream', 'calendar_entry_id');
    }
}
