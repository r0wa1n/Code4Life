<?php

class Sample
{

    public $sampleId;
    public $carriedBy;
    public $rank;
    public $expertiseGain;
    public $health;
    public $costA, $costB, $costC, $costD, $costE;

    function log()
    {
        error_log("Player take a new sample: " . $this->sampleId);
        error_log("Sample need :");

        error_log("    A :" . $this->costA);
        error_log("    B :" . $this->costB);
        error_log("    C :" . $this->costC);
        error_log("    D :" . $this->costD);
        error_log("    E :" . $this->costE);
    }
}