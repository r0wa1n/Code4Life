<?php

class Player
{

    public $target;
    public $score;
    public $eta;
    public $storageMolecules = [];
    public $expertiseMolecules = [];
    public $availableMolecules = [];
    public $otherPlayer;

    // custom properties
    public $samples = [];
    public $sumStorage;
    public $currentRank;

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
        if (($this->isFullOfMolecules() || is_null($this->findWhichMoleculeTakeForSample())) && $this->hasAtLeastOneCompletedSample()) {
            $this->goToModule(Module::LABORATORY);
        } else if (!$this->hasAtLeastOneSampleCanBeProduced()) {
            if ($this->otherPlayer->target == Module::LABORATORY) {
                echo ("WAIT\n");
            } else {
                $this->goToModule(Module::DIAGNOSIS);
            }
        } else if(!$this->isFullOfMolecules()) {
            $moleculeToTake = $this->findWhichMoleculeTakeForSample();
            echo("CONNECT $moleculeToTake\n");
        } else {
            $this->goToModule(Module::DIAGNOSIS);
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
        $sumExpertise = array_sum($this->expertiseMolecules);
        if ($sumExpertise >= 12) {
            $rank = 3;
        } else if ($sumExpertise >= 8) {
            $rank = 2;
        } else {
            $rank = 1;
        }
        $this->currentRank = $rank;

        return $rank;
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
                && $sample->canBeProduced($this->availableMolecules, $this->storageMolecules)
            ) {
                return $sample->getFirstMoleculeMissing($this->storageMolecules);
            }
        }

        error_log('No molecule to take');
        return null;
    }

    function updateSamples()
    {
        // Check if all my samples are diagnosed
        if (is_null($this->getFirstUndiagnosedSample())) {
            $cacheSampleCanBeProduced = [];
            foreach ($this->samples as $sample) {
                $cacheSampleCanBeProduced[$sample->sampleId] = $sample->canBeProduced($this->availableMolecules, $this->storageMolecules);
            }
            uasort($this->samples, function ($s1, $s2) use ($cacheSampleCanBeProduced) {
                if ($s1->carriedBy == 0 && $s2->carriedBy != 0) {
                    return -1;
                } else if ($s1->carriedBy != 0 && $s2->carriedBy == 0) {
                    return 1;
                } else if ($s1->carriedBy != 0 && $s2->carriedBy != 0) {
                    return 0;
                } else {
                    if ($cacheSampleCanBeProduced[$s1->sampleId] && !$cacheSampleCanBeProduced[$s2->sampleId]) {
                        return -1;
                    } else if (!$cacheSampleCanBeProduced[$s1->sampleId] && $cacheSampleCanBeProduced[$s2->sampleId]) {
                        return 1;
                    } else if (!$cacheSampleCanBeProduced[$s1->sampleId] && !$cacheSampleCanBeProduced[$s2->sampleId]) {
                        return 0;
                    } else {
                        if ($s1->health == $s2->health) {
                            return 0;
                        }
                        return ($s1->health > $s2->health) ? -1 : 1;
                    }
                }
            });
            foreach ($this->samples as $sample) {
                if ($sample->carriedBy == 0) {
                    foreach ($sample->costs as $molecule => &$count) {
                        $count = max($count - $this->expertiseMolecules[$molecule], 0);
                    }
                    if (!$sample->completed && $sample->canBePushToLaboratory($this->storageMolecules)) {
                        error_log('Sample ' . $sample->sampleId . ' is tag completed');
                        // update storage player
                        foreach ($sample->costs as $molecule => $count) {
                            if ($count > 0) {
                                error_log("Old storage of molecule $molecule was " . $this->storageMolecules[$molecule] . ", take $count molecules of sample " . $sample->sampleId);
                                $this->storageMolecules[$molecule] -= $count;
                            }
                        }
                        $sample->completed = true;
                        error_log('Gain of molecule ' . $sample->expertiseGain);
                        $this->storageMolecules[strtolower($sample->expertiseGain)]++;
                    }
                }
            }
        }
    }

    function hasAtLeastOneSampleCanBeProduced()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0
                && $sample->canBeProduced($this->availableMolecules, $this->storageMolecules)
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
                && $sample->health > $best && $sample->canBeProduced($this->availableMolecules, $this->storageMolecules)
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
        $isFull = $this->sumStorage >= 10;
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