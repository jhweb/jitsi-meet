<?php

use yii\db\Migration;

/**
 * Class m251229_143000_add_highlights_and_sharing
 */
class m251229_143000_add_highlights_and_sharing extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('jitsi_live_stream', 'highlights_url', $this->text());
        $this->addColumn('jitsi_live_stream', 'screen_sharing_url', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('jitsi_live_stream', 'highlights_url');
        $this->dropColumn('jitsi_live_stream', 'screen_sharing_url');
    }
}
