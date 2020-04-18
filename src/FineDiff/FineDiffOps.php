<?php

namespace ThikDev\FineDiff;

/**
 * FineDiff ops
 *
 * Collection of ops
 */
class FineDiffOps
{
    public $edits = array();

    public function appendOpcode($opcode, $from, $from_offset, $from_len)
    {
        if ($opcode === 'c') {
            $this->edits[] = new FineDiffCopyOp($from_len);
        } elseif ($opcode === 'd') {
            $this->edits[] = new FineDiffDeleteOp($from_len);
        } else /* if ( $opcode === 'i' ) */ {
            $this->edits[] = new FineDiffInsertOp(substr($from, $from_offset, $from_len));
        }
    }
}
