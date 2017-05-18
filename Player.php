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
    public $completedSamples = [];

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
        } else if ($this->atLeastOneSampleCanBeProduced()) {
            $this->goToModule(Module::MOLECULES);
        } else {
            $firstDiagnosedSample = $this->getFirstDiagnosedSample();
            if (!is_null($firstDiagnosedSample)) {
                echo("CONNECT $firstDiagnosedSample\n");
            } else {
                $this->goToModule(Module::SAMPLES);
            }
        }
    }

    function moleculesModule()
    {
        $this->logMySamples();
        error_log('My completed samples: ' . implode(' - ', $this->completedSamples));
        $this->updateCompletedSamples();

        // TODO manage rank 3 samples
        if (($this->isFullOfMolecules() || is_null($this->findWhichMoleculeTakeForSample())) && $this->hasAtLeastOneCompletedSample()) {
            $this->goToModule(Module::LABORATORY);
        } else if (!$this->atLeastOneSampleCanBeProduced()) {
            $this->goToModule(Module::DIAGNOSIS);
        } else {
            $moleculeToTake = $this->findWhichMoleculeTakeForSample();
            // TODO count number of turn he's waiting, and at 7 turns for example, go rage quit
            if (is_null($moleculeToTake)) {
                echo("WAIT\n");
            } else {
                echo("CONNECT $moleculeToTake\n");
            }
        }
    }

    function laboratoryModule()
    {
        if ($this->hasAtLeastOneCompletedSample()) {
            $completedSample = $this->getFirstCompletedSample();
            echo("CONNECT $completedSample\n");
        } else if ($this->atLeastOneSampleCanBeProduced()) {
            $this->goToModule(Module::MOLECULES);
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
        return array_shift($this->completedSamples);
    }

    function findWhichMoleculeTakeForSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0
                && !in_array($sample->sampleId, $this->completedSamples)
                && $sample->canBeProduced($this->availableMolecules, $this->expertiseMolecules, $this->storageMolecules, $this->getCompletedSampleMolecules())
            ) {
                return $sample->getFirstMoleculeMissing($this->storageMolecules, $this->expertiseMolecules);
            }
        }

        error_log('No molecule to take');
        return null;
    }

    function updateCompletedSamples()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0
                && !in_array($sample->sampleId, $this->completedSamples)
                && $sample->isCompleted($this->storageMolecules, $this->expertiseMolecules, $this->getCompletedSampleMolecules())
            ) {
                $this->completedSamples[] = $sample->sampleId;
            }
        }
    }

    function getCompletedSampleMolecules()
    {
        $combineMolecules = [
            'a' => 0,
            'b' => 0,
            'c' => 0,
            'd' => 0,
            'e' => 0
        ];
        // Combine all molecules of each completed samples
        foreach ($this->completedSamples as $sampleId) {
            foreach ($this->samples[$sampleId]->costs as $molecule => $count) {
                $combineMolecules[$molecule] += $count - $this->expertiseMolecules[$molecule];
            }
        }

        return $combineMolecules;
    }

    function atLeastOneSampleCanBeProduced()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0
                && $sample->canBeProduced($this->availableMolecules, $this->expertiseMolecules, $this->storageMolecules, $this->getCompletedSampleMolecules())
            ) {
                return true;
            }
        }

        return false;
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
        return array_sum($this->expertiseMolecules) >= 6 || !in_array(0, $this->expertiseMolecules);
    }

    function hasAtLeastOneCompletedSample()
    {
//        foreach ($this->samples as $sample) {
//            if ($sample->carriedBy == 0 && $sample->isCompleted($this->storageMolecules, $this->expertiseMolecules)) {
//                return true;
//            }
//        }
//
//        return false;
        return count($this->completedSamples) >= 1;
    }

    function logMySamples()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0) {
                $sample->log();
            }
        }
    }

    function isFullOfMolecules()
    {
        $isFull = array_sum($this->storageMolecules) == 10;
        if ($isFull) {
            error_log('Player is full of molecules');
        }

        return $isFull;
    }

    function isMoving()
    {
        return $this->eta > 0;
    }
}