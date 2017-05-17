<?php

class Project
{

    public $costA, $costB, $costC, $costD, $costE;

    function log()
    {
        error_log("Project need expertises:");

        error_log("    A :" . $this->costA);
        error_log("    B :" . $this->costB);
        error_log("    C :" . $this->costC);
        error_log("    D :" . $this->costD);
        error_log("    E :" . $this->costE);
    }
}