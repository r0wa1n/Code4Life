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
                if (is_null($cloudSampleId)) {
                    $this->goToModule(Module::SAMPLES);
                } else {
                    echo("CONNECT $cloudSampleId\n");
                }
            }
        }
    }

    function moleculesModule()
    {
        $this->logMySamples();
        // TODO manage rank 3 samples
        if (($this->isFullOfMolecules() || is_null($this->findWhichMoleculeTakeForSample())) && $this->hasAtLeastOneCompletedSample()) {
            $this->goToModule(Module::LABORATORY);
        } else if (!$this->hasAtLeastOneSampleCanBeProduced()) {
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
        } else if ($this->hasAtLeastOneSampleCanBeProduced()) {
            $this->goToModule(Module::MOLECULES);
        } else if (!is_null($this->getBestCloudSampleCanBeProduced())) {
            $this->goToModule(Module::DIAGNOSIS);
        } else {
            $this->goToModule(Module::SAMPLES);
        }
    }

    function generateRank()
    {
        if ($this->hasEnoughExpertiseToPassRank(2)) {
            return 2;
        } else if ($this->hasEnoughExpertiseToPassRank(3)) {
            return 3;
        } else {
            return 1;
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
            if ($sample->carriedBy == 0 && $sample->completed) {
                return $sample->sampleId;
            }
        }

        return null;
    }

    function findWhichMoleculeTakeForSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0
                && !$sample->completed
                && $sample->canBeProduced($this->availableMolecules, $this->storageMolecules, $this->getStorageMoleculesUsed())
            ) {
                return $sample->getFirstMoleculeMissing($this->storageMolecules);
            }
        }

        error_log('No molecule to take');
        return null;
    }

    function updateSamples()
    {
        // TODO sort sample by health and produced
        foreach ($this->samples as $sample) {
            foreach ($sample->costs as $molecule => &$count) {
                $count = max($count - $this->expertiseMolecules[$molecule], 0);
            }
            if ($sample->carriedBy == 0 && !$sample->completed && $sample->canBePushToLaboratory($this->storageMolecules, $this->getStorageMoleculesUsed())) {
                $sample->completed = true;
            }
        }
    }

    function getStorageMoleculesUsed()
    {
        $combineMolecules = [
            'a' => 0,
            'b' => 0,
            'c' => 0,
            'd' => 0,
            'e' => 0
        ];
        // Combine all molecules of each completed samples
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0 && $sample->completed) {
                foreach ($sample->costs as $molecule => $count) {
                    $combineMolecules[$molecule] += $count;
                }
            }
        }

        return $combineMolecules;
    }

    function hasAtLeastOneSampleCanBeProduced()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0
                && $sample->canBeProduced($this->availableMolecules, $this->storageMolecules, $this->getStorageMoleculesUsed())
            ) {
                error_log('Sample ' . $sample->sampleId . ' can be produced');

                return true;
            }
        }

        return false;
    }

    function getBestCloudSampleCanBeProduced()
    {
        $best = 0;
        $bestId = null;
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == -1
                && $sample->health > $best && $sample->canBeProduced($this->availableMolecules, $this->storageMolecules, $this->getStorageMoleculesUsed())
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

    function hasEnoughExpertiseToPassRank($rank)
    {
        if ($rank == 2) {
            return array_sum($this->expertiseMolecules) >= 6 || !in_array(0, $this->expertiseMolecules);
        } else {
            return array_sum($this->expertiseMolecules) >= 10 && !in_array(0, $this->expertiseMolecules);
        }
    }

    function hasAtLeastOneCompletedSample()
    {
        return !is_null($this->getFirstCompletedSample());
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