<?php

class Game
{

    public $p1, $p2;
    public $samples = [];
    public $projects = [];
    public $availableA, $availableB, $availableC, $availableD, $availableE;

    function __construct()
    {
        $this->p1 = new Player();
        $this->p2 = new Player();
    }

    function scanSamples()
    {
        $this->samples = [];
        fscanf(STDIN, "%d",
            $sampleCount
        );
        for ($i = 0; $i < $sampleCount; $i++) {
            $sample = new Sample();
            fscanf(STDIN, "%d %d %d %s %d %d %d %d %d %d",
                $sample->sampleId,
                $sample->carriedBy,
                $sample->rank,
                $sample->expertiseGain,
                $sample->health,
                $sample->costA,
                $sample->costB,
                $sample->costC,
                $sample->costD,
                $sample->costE
            );
            $this->samples[$sample->sampleId] = $sample;
        }

        $this->p1->samples = $this->samples;
        $this->p2->samples = $this->samples;
    }

    function scanPlayer(Player $p)
    {
        fscanf(STDIN, "%s %d %d %d %d %d %d %d %d %d %d %d %d",
            $p->target,
            $p->eta,
            $p->score,
            $p->storageA,
            $p->storageB,
            $p->storageC,
            $p->storageD,
            $p->storageE,
            $p->expertiseA,
            $p->expertiseB,
            $p->expertiseC,
            $p->expertiseD,
            $p->expertiseE
        );
    }

    function scanProjects()
    {
        fscanf(STDIN, "%d",
            $projectCount
        );
        for ($i = 0; $i < $projectCount; $i++) {
            $project = new Project();
            fscanf(STDIN, "%d %d %d %d %d",
                $project->costA,
                $project->costB,
                $project->costC,
                $project->costD,
                $project->costE
            );
            $project->log();
            $this->projects[] = $project;
        }
    }

    function scanAvailableMolecules()
    {
        fscanf(STDIN, "%d %d %d %d %d",
            $this->availableA,
            $this->availableB,
            $this->availableC,
            $this->availableD,
            $this->availableE
        );

        $this->p1->availableA = $this->availableA;
        $this->p1->availableB = $this->availableB;
        $this->p1->availableC = $this->availableC;
        $this->p1->availableD = $this->availableD;
        $this->p1->availableE = $this->availableE;
    }

    function decideWhatToDo()
    {
        $this->p1->decideWhatToDo();
    }
}