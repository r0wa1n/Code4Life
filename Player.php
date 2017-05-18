<?php

class Player
{

    public $target;
    public $score;
    public $eta;
    public $storageMolecules = [];
    public $expertiseMolecules = [];
    public $availableMolecules = [];

    // custom properties
    public $samples = [];

    public $matrice = [
        'START_POS' => [
            Module::SAMPLES => 2,
            Module::DIAGNOSIS => 2,
            Module::MOLECULES => 2,
            Module::LABORATORY => 2
        ], Module::SAMPLES => [
            Module::DIAGNOSIS => 3,
            Module::MOLECULES => 3,
            Module::LABORATORY => 3
        ], Module::DIAGNOSIS => [
            Module::SAMPLES => 3,
            Module::MOLECULES => 3,
            Module::LABORATORY => 4
        ], Module::MOLECULES => [
            Module::SAMPLES => 3,
            Module::DIAGNOSIS => 3,
            Module::LABORATORY => 3,
        ], Module::LABORATORY => [
            Module::SAMPLES => 3,
            Module::DIAGNOSIS => 4,
            Module::MOLECULES => 3
        ],
    ];

    function goToModule($module)
    {
        error_log("Player move to module $module\n");
        echo("GOTO $module\n");
    }

    function sampleModule()
    {
        if ($this->hasEnoughSamplesToDiagnosed()) {
            $this->goToModule(Module::DIAGNOSIS);
        } else {
            $rank = $this->generateRank();
            echo("CONNECT $rank\n");
        }
    }

    function diagnosisModule()
    {
        $firstUndiagnosedSample = $this->getFirstUndiagnosedSample();
        if (!is_null($firstUndiagnosedSample)) {
            echo("CONNECT $firstUndiagnosedSample\n");
        } else if ($this->hasAtLeastOneSampleCanBeProduced()) {
            $this->goToModule(Module::MOLECULES);
        } else {
            $firstDiagnosedSample = $this->getFirstDiagnosedSample();
            if (!is_null($firstDiagnosedSample)) {
                echo("CONNECT $firstDiagnosedSample\n");
            } else {
                $cloudSampleId = $this->getBestCloudSampleCanBeProduced();
                if(is_null($cloudSampleId)) {
                    $this->goToModule(Module::SAMPLES);
                } else {
                    echo("CONNECT $cloudSampleId\n");
                }
            }
        }
    }

    function moleculesModule()
    {
        if (!$this->hasAtLeastOneSampleCanBeProduced()) {
            $this->goToModule(Module::DIAGNOSIS);
        } else if (!$this->hasAtLeastOneCompletedSample()) {
            $moleculeToTake = $this->findWhichMoleculeTakeForSample();
            if (is_null($moleculeToTake)) {
                echo("WAIT\n");
            } else {
                echo("CONNECT $moleculeToTake\n");
            }
        } else {
            $this->goToModule(Module::LABORATORY);
        }
    }

    function laboratoryModule()
    {
        if ($this->hasAtLeastOneCompletedSample()) {
            $completedSample = $this->getFirstCompletedSample();
            echo("CONNECT $completedSample\n");
        } else if ($this->hasAtLeastOneSampleCanBeProduced()) {
            $this->goToModule(Module::MOLECULES);
        } else if(!is_null($this->getBestCloudSampleCanBeProduced())){
            $this->goToModule(Module::DIAGNOSIS);
        } else {
            $this->goToModule(Module::SAMPLES);
        }
    }

    function generateRank()
    {
        if (!$this->hasEnoughExpertise()) {
            return 1;
        } else {
            return 2;
        }
    }

    function getFirstUndiagnosedSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0 && $sample->health == -1) {
                return $sample->sampleId;
            }
        }

        return null;
    }

    function getFirstDiagnosedSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0 && $sample->health != -1) {
                return $sample->sampleId;
            }
        }

        return null;
    }

    function getFirstCompletedSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0 && $sample->isCompleted($this->storageMolecules, $this->expertiseMolecules)) {
                return $sample->sampleId;
            }
        }
    }

    function findWhichMoleculeTakeForSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0 && $sample->canBeProduced($this->availableMolecules, $this->expertiseMolecules, $this->storageMolecules)) {
                return $sample->getFirstMoleculeMissing($this->storageMolecules, $this->expertiseMolecules);
            }
        }
        return null;
    }

    function hasAtLeastOneSampleCanBeProduced()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0
                && $sample->canBeProduced($this->availableMolecules, $this->expertiseMolecules, $this->storageMolecules)
            ) {
                return true;
            }
        }

        return false;
    }

    function getBestCloudSampleCanBeProduced() {
        $best = PHP_INT_MIN;
        $bestId = null;
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == -1
                && $sample->health > $best && $sample->canBeProduced($this->availableMolecules, $this->expertiseMolecules, $this->storageMolecules)
            ) {
                $best = $sample->health;
                $bestId = $sample->sampleId;
            }
        }
        return $bestId;
    }

    function hasEnoughSamplesToDiagnosed()
    {
        $samplesCarriedByMe = 0;
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0) {
                $samplesCarriedByMe++;
            }
        }

        return $samplesCarriedByMe == 3;
    }

    function hasEnoughExpertise()
    {
        return array_sum($this->expertiseMolecules) >= 7 || !in_array(0, $this->expertiseMolecules);
    }

    function hasAtLeastOneCompletedSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0 && $sample->isCompleted($this->storageMolecules, $this->expertiseMolecules)) {
                return true;
            }
        }

        return false;
    }

    function isMoving()
    {
        return $this->eta > 0;
    }
}