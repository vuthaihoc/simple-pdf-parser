<?php

namespace ThikDev\FineDiff;

class FineDiffDeleteOp extends FineDiffOp
{
    private $fromLen;

    public function __construct($len)
    {
        $this->fromLen = $len;
    }
    public function getFromLen()
    {
        return $this->fromLen;
    }
    public function getToLen()
    {
        return 0;
    }
    public function getOpcode()
    {
        if ($this->fromLen === 1) {
            return 'd';
        }
        return "d{$this->fromLen}";
    }
}
