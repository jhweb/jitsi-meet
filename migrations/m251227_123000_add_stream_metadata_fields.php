<?php

use yii\db\Migration;

/**
 * Class m251227_123000_add_stream_metadata_fields
 */
class m251227_123000_add_stream_metadata_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('jitsi_live_stream', 'transcription_url', $this->text()->null());
        $this->addColumn('jitsi_live_stream', 'chat_log_url', $this->text()->null());
        $this->addColumn('jitsi_live_stream', 'file_urls', $this->text()->null());
        $this->addColumn('jitsi_live_stream', 'reactions', $this->text()->null());
        
        // Add index for potentially searching if needed later, though not critical for TEXT usually
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('jitsi_live_stream', 'transcription_url');
        $this->dropColumn('jitsi_live_stream', 'chat_log_url');
        $this->dropColumn('jitsi_live_stream', 'file_urls');
        $this->dropColumn('jitsi_live_stream', 'reactions');
    }
}
