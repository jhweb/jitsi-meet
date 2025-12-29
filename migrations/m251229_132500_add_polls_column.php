<?php

use yii\db\Migration;

/**
 * Class m251229_132500_add_polls_column
 */
class m251229_132500_add_polls_column extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('jitsi_live_stream', 'polls', $this->text());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('jitsi_live_stream', 'polls');
    }
}
