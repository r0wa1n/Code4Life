<?php

class Game
{

    public $p1, $p2;
    public $samples = [];
    public $projects = [];
    public $availableMolecules = [];

    function __construct()
    {
        $this->p1 = new Player();
        $this->p2 = new Player();
    }

    function next()
    {
        // Check if player is running
        if ($this->p1->isMoving()) {
            // Action will be ignored
            echo ("\n");
        } else {
            $this->decideWhatToDo();
        }
    }

    function decideWhatToDo() {
        switch ($this->p1->target) {
            case Module::SAMPLES:
                $this->p1->sampleModule();
                break;
            case Module::DIAGNOSIS:
                $this->p1->diagnosisModule();
                break;
            case Module::MOLECULES:
                $this->p1->moleculesModule();
                break;
            case Module::LABORATORY:
                $this->p1->laboratoryModule();
                break;
            default:
                // First turn
                $this->p1->goToModule(Module::SAMPLES);
                break;
        }
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
                $sample->costs['a'],
                $sample->costs['b'],
                $sample->costs['c'],
                $sample->costs['d'],
                $sample->costs['e']
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
            $p->storageMolecules['a'],
            $p->storageMolecules['b'],
            $p->storageMolecules['c'],
            $p->storageMolecules['d'],
            $p->storageMolecules['e'],
            $p->expertiseMolecules['a'],
            $p->expertiseMolecules['b'],
            $p->expertiseMolecules['c'],
            $p->expertiseMolecules['d'],
            $p->expertiseMolecules['e']
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
                $project->costs['a'],
                $project->costs['b'],
                $project->costs['c'],
                $project->costs['d'],
                $project->costs['e']
            );
            $project->log();
            $this->projects[] = $project;
        }
    }

    function scanAvailableMolecules()
    {
        fscanf(STDIN, "%d %d %d %d %d",
            $this->availableMolecules['a'],
            $this->availableMolecules['b'],
            $this->availableMolecules['c'],
            $this->availableMolecules['d'],
            $this->availableMolecules['e']
        );

        $this->p1->availableMolecules = $this->availableMolecules;
    }
}