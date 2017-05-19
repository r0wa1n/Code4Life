<?php

class Player
{
    public $game;
    public $target;
    public $score;
    public $eta;
    public $storageMolecules = [];
    public $expertiseMolecules = [];
    public $carriedBy;
    public $opponent;
    public $samples = [];
    public $sumStorage;

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
        } else if ((!$this->hasTimeToFinishOtherOne($this->matrice[Module::DIAGNOSIS][Module::LABORATORY]) && $this->hasAtLeastOneCompletedSample()) || $this->getNumberCompletedSamples() >= 2) {
            error_log('Game is almost finished, go complete a sample');
            $this->goToModule(Module::LABORATORY);
        } else if ($this->numberOfSamplesCarriedByUs() < 3) {
            $this->manageCloudSamples();
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

        if (($this->isFullOfMolecules() || !$this->hasTimeToFinishOtherOne($this->matrice[Module::MOLECULES][Module::LABORATORY])) && $this->hasAtLeastOneCompletedSample()) {
            $this->goToModule(Module::LABORATORY);
        } else if (!is_null($moleculeToScrew = $this->checkIfWeCanScrewOpponentMolecule())) {
            echo ("CONNECT $moleculeToScrew\n");
        } else if (is_null($this->findWhichMoleculeTakeForSample(false)) && $this->hasAtLeastOneCompletedSample()) {
            $this->goToModule(Module::LABORATORY);
        } else if (!$this->hasAtLeastOneSampleCanBeProduced()) {
            if ($this->opponent->target == Module::LABORATORY) {
                echo("WAIT\n");
            } else {
                $this->goToModule(Module::DIAGNOSIS);
            }
        } else if (!$this->isFullOfMolecules()) {
            $moleculeToTake = $this->findWhichMoleculeTakeForSample(true);
            echo("CONNECT $moleculeToTake\n");
        } else {
            $this->goToModule(Module::DIAGNOSIS);
        }
    }

    function checkIfWeCanScrewOpponentMolecule()
    {
        if ($this->isFullOfMolecules() || $this->opponent->target == Module::MOLECULES) {
            return null;
        } else {
            foreach ($this->samples as $sample) {
                if ($sample->carriedBy == $this->opponent->carriedBy && $sample->health == -1 && $sample->canBeProduced($this->game->availableMolecules, $this->opponent->storageMolecules, $this->opponent->getSlotsAvailable())) {
                    $missingMolecules = $sample->getMissingMolecules($this->opponent->storageMolecules);
                    // Check if for all missing molecules, if i can screw him one
                    foreach ($missingMolecules as $molecule => $missingCount) {
                        if ($this->game->availableMolecules[$molecule] - $missingCount == 0) {
                            $this->game->sentences[] = "I have screwed you !!";
                            return $molecule;
                        }
                    }
                }
            }

            return null;
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
        /*$sumMissingExpertise = $this->getSumMissingExpertise();

        if ($sumMissingExpertise) {
            if ($this->numberOfRankInMyPossession(1) < 1) {
                return 1;
            } else if ($this->numberOfRankInMyPossession(2) < 1) {
                return 2;
            }
        }*/

        $sumExpertise = array_sum($this->expertiseMolecules);
        if (($sumExpertise >= 10 && $this->numberOfRankInMyPossession(3) < 1) || ($sumExpertise >= 15 && $this->numberOfRankInMyPossession(3) < 2)) {
            return 3;
        } else if ($sumExpertise >= 7 && $this->numberOfRankInMyPossession(2) <= 1) {
            return 2;
        } else {
            return 1;
        }
    }

    function manageCloudSamples()
    {
        $cloudSampleId = $this->getBestCloudSampleCanBeProduced();
        if (is_null($cloudSampleId)) {
            error_log('No cloud sample which can be produced found');
            if ($this->hasAtLeastOneSampleCanBeProduced()) {
                error_log('Going to molecule to produce one of mine');
                $this->goToModule(Module::MOLECULES);
            } else {
                error_log('Going to samples module to take new ones');
                $this->goToModule(Module::SAMPLES);
            }
        } else {
            error_log('Take sample ' . $cloudSampleId . ' from the cloud');
            echo("CONNECT $cloudSampleId\n");
        }
    }

    function getSumMissingExpertise()
    {
        $oneProjectSoonFinish = false;
        foreach ($this->game->projects as $project) {
            $projectSoonFinish = true;
            foreach ($project->costs as $molecule => $count) {
                if ($count - $this->expertiseMolecules[$molecule] > 1 && $count - $this->expertiseMolecules[$molecule] > 0) {
                    $projectSoonFinish = false;
                }
            }
            !$projectSoonFinish ?: $oneProjectSoonFinish = true;
        }
        if ($oneProjectSoonFinish) {

            $oneProjectSoonFinish = false;
            foreach ($this->game->projects as $project) {
                $projectSoonFinish = 0;
                foreach ($project->costs as $molecule => $count) {
                    if ($count - $this->expertiseMolecules[$molecule] == 0) {
                        $projectSoonFinish++;
                    }
                }
                $projectSoonFinish != 5 ?: $oneProjectSoonFinish = true;
            }
        }
        return $oneProjectSoonFinish;
    }

    function hasTimeToFinishOtherOne($distance)
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == $this->carriedBy && !$sample->completed
                && $sample->canBeProduced($this->game->availableMolecules, $this->storageMolecules, $this->getSlotsAvailable())
            ) {
                $timeToFinishSampleAndGoPutIt = $sample->timeToCompleteIt($this->storageMolecules) + $distance + 1; //3: movement between MOLECULE and LABORATORY and 1 for connect
                error_log("Temps pour finir le sample : $timeToFinishSampleAndGoPutIt");
                if ($this->game->turn + $timeToFinishSampleAndGoPutIt < 200) {
                    return true;
                }
            }
        }
        return false;
    }

    function getFirstUndiagnosedSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == $this->carriedBy && $sample->health == -1) {
                return $sample->sampleId;
            }
        }

        return null;
    }

    function getFirstDiagnosedSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == $this->carriedBy && $sample->health != -1) {
                return $sample->sampleId;
            }
        }

        return null;
    }

    function getFirstCompletedSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == $this->carriedBy && $sample->completed) {
                return $sample->sampleId;
            }
        }

        return null;
    }

    function getFirstUnProducedSample()
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == $this->carriedBy && !$sample->completed && !$sample->canBeProduced($this->game->availableMolecules, $this->storageMolecules, $this->getSlotsAvailable())) {
                return $sample->sampleId;
            }
        }

        return null;
    }

    function getNumberCompletedSamples()
    {
        $n = 0;
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0 && $sample->completed) {
                $n++;
            }
        }

        return $n;
    }

    function numberOfRankInMyPossession($rank)
    {
        $n = 0;
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == $this->carriedBy && $sample->rank == $rank) {
                $n++;
            }
        }
        return $n;
    }

    function findWhichMoleculeTakeForSample($compareWithOpponent)
    {
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == $this->carriedBy
                && !$sample->completed
                && $sample->canBeProduced($this->game->availableMolecules, $this->storageMolecules, $this->getSlotsAvailable())
            ) {
                return $sample->getFirstMoleculeMissing($this->storageMolecules, $compareWithOpponent ? $this->game->p2 : null);
            }
        }

        error_log('No molecule to take');
        return null;
    }

    function numberOfSamplesCarriedByUs()
    {
        $n = 0;

        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == $this->carriedBy) {
                $n++;
            }
        }
        return $n;
    }

    function updateSamples()
    {
        // Check if all my samples are diagnosed
        if (is_null($this->getFirstUndiagnosedSample())) {
            $cacheSampleCanBeProduced = [];
            foreach ($this->samples as $sample) {
                if ($sample->carriedBy == $this->carriedBy) {
                    $cacheSampleCanBeProduced[$sample->sampleId] = $sample->canBeProduced($this->game->availableMolecules, $this->storageMolecules, $this->getSlotsAvailable());
                }
            }
            uasort($this->samples, function ($s1, $s2) use ($cacheSampleCanBeProduced) {
                if ($s1->carriedBy == $this->carriedBy && $s2->carriedBy != $this->carriedBy) {
                    return -1;
                } else if ($s1->carriedBy != $this->carriedBy && $s2->carriedBy == $this->carriedBy) {
                    return 1;
                } else if ($s1->carriedBy != $this->carriedBy && $s2->carriedBy != $this->carriedBy) {
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
                if ($sample->carriedBy == $this->carriedBy) {
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
            if ($sample->carriedBy == $this->carriedBy
                && $sample->canBeProduced($this->game->availableMolecules, $this->storageMolecules, $this->getSlotsAvailable())
            ) {
                error_log('Sample ' . $sample->sampleId . ' can be produced');

                return true;
            }
        }

        return false;
    }

    function getBestCloudSampleCanBeProduced()
    {
        error_log('Search sample inside the cloud which can be produced');
        $best = 0;
        $bestId = null;
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == -1
                && $sample->health > $best && $sample->canBeProduced($this->game->availableMolecules, $this->storageMolecules, $this->getSlotsAvailable())
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
            if ($sample->carriedBy == $this->carriedBy) {
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
            if ($sample->carriedBy == $this->carriedBy) {
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

    function getSlotsAvailable()
    {
        return 10 - $this->sumStorage;
    }

    function isMoving()
    {
        return $this->eta > 0;
    }
}