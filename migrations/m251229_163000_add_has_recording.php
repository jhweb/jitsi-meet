<?php

use yii\db\Migration;

/**
 * Class m251229_163000_add_has_recording
 */
class m251229_163000_add_has_recording extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('jitsi_live_stream', 'has_recording', $this->boolean()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('jitsi_live_stream', 'has_recording');
    }
}
