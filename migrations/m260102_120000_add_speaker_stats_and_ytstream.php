<?php

use yii\db\Migration;

/**
 * Class m260102_120000_add_speaker_stats_and_ytstream
 */
class m260102_120000_add_speaker_stats_and_ytstream extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('jitsi_live_stream', 'speaker_stats', $this->text()->null());
        $this->addColumn('jitsi_live_stream', 'rtcstats_url', $this->text()->null()); // Check if text is enough or string
        $this->addColumn('jitsi_live_stream', 'ytstream_url', $this->text()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('jitsi_live_stream', 'speaker_stats');
        $this->dropColumn('jitsi_live_stream', 'rtcstats_url');
        $this->dropColumn('jitsi_live_stream', 'ytstream_url');
    }
}
