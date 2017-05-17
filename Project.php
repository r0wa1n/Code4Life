<?php

class Project
{
    public $costs;

    function log()
    {
        error_log("Project need expertises:");

        error_log("    A :" . $this->costs['a']);
        error_log("    B :" . $this->costs['b']);
        error_log("    C :" . $this->costs['c']);
        error_log("    D :" . $this->costs['d']);
        error_log("    E :" . $this->costs['e']);
    }
}