<?php

class Player
{

    public $target;
    public $score;
    public $storageA, $storageB, $storageC, $storageD, $storageE;
    public $expertiseA, $expertiseB, $expertiseC, $expertiseD, $expertiseE;
    public $availableA, $availableB, $availableC, $availableD, $availableE;

    // custom properties
    public $currentSampleIds = [];
    public $currentProcessingSampleId = null;
    public $askedSampleRanks = [
        1 => 0,
        2 => 0,
        3 => 0
    ];
    public $inMovement = [
        'direction' => null,
        'movementLeft' => 0
    ];

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
        $this->inMovement['movementLeft']--;
        echo("GOTO $module\n");
    }

    function checkDistance()
    {
        if ($this->inMovement['movementLeft'] > 0) {
            $this->goToModule($this->inMovement['direction']);
            return false;
        } else {
            return true;
        }
    }

    function decideWhatToDo()
    {
        // Check if he's in movement
        if ($this->checkDistance()) {
            switch ($this->target) {
                case Module::SAMPLES:
                    error_log('Arrived to samples module, available askedSampleRanks: ' . implode('-', $this->askedSampleRanks));
                    if ($this->getSampleRanksSum() < CARRY_SAMPLES) {
                        //if (in_array(3, $this->askedSampleRanks)) {
                        //$randomRank = rand(1, 2);
                        $randomRank = 2;
                        //} else {
                        //    $randomRank = rand(1, 3);
                        //}
                        echo("CONNECT $randomRank\n");
                        $this->askedSampleRanks[$randomRank]++;
                    } else {
                        $this->initMovement(Module::DIAGNOSIS);
                        $this->goToModule(Module::DIAGNOSIS);
                    }
                    break;
                case Module::DIAGNOSIS:
                    error_log('Arrived to diagnosis module, available currentSampleIds: ' . implode('-', $this->currentSampleIds));
                    if (count($this->currentSampleIds) < CARRY_SAMPLES) {
                        error_log('Not enought samples, search one...');
                        $sampleId = $this->searchSample();
                        if (is_null($sampleId)) {
                            $this->initMovement(Module::SAMPLES);
                            $this->goToModule(Module::SAMPLES);
                        } else {
                            $this->currentSampleIds[] = $sampleId;
                            echo("CONNECT " . $sampleId . "\n");
                        }
                    } else {
                        error_log('Check if we can make one sample');
                        // Check if we can at least make one
                        $sampleIdToProcess = $this->getSampleToProcess();
                        if (is_null($sampleIdToProcess)) {
                            $sampleIdToDelete = array_shift($this->currentSampleIds);
                            error_log('No sample can be produced, send this one to cloud: ' . $sampleIdToDelete);
                            $this->askedSampleRanks[$this->samples[$sampleIdToDelete]->rank]--;
                            echo("CONNECT $sampleIdToDelete\n");
                        } else {
                            error_log('We can make sample ' . $sampleIdToProcess);
                            $this->currentProcessingSampleId = $sampleIdToProcess;
                            $this->initMovement(Module::MOLECULES);
                            $this->goToModule(Module::MOLECULES);
                        }
                    }
                    break;
                case Module::MOLECULES:
                    if ($this->hasEnoughtMolecule()) {
                        $this->initMovement(Module::LABORATORY);
                        $this->goToModule(Module::LABORATORY);
                    }
                    break;
                case Module::LABORATORY:
                    if (!is_null($this->currentProcessingSampleId)) {
                        echo("CONNECT " . $this->currentProcessingSampleId . "\n");
                        $this->askedSampleRanks[$this->samples[$this->currentProcessingSampleId]->rank]--;
                        // Delete from current samples
                        unset($this->currentSampleIds[$this->currentProcessingSampleId]);
                        $this->currentProcessingSampleId = null;
                    } else if (count($this->currentSampleIds) > 0) {
                        // Check if we can at least make one
                        $sampleIdToProcess = $this->getSampleToProcess();
                        if (!is_null($sampleIdToProcess)) {
                            $this->currentProcessingSampleId = $sampleIdToProcess;
                            $this->initMovement(Module::MOLECULES);
                            $this->goToModule(Module::MOLECULES);
                        } else {
                            $this->initMovement(Module::SAMPLES);
                            $this->goToModule(Module::SAMPLES);
                        }
                    } else {
                        $this->initMovement(Module::SAMPLES);
                        $this->goToModule(Module::SAMPLES);
                    }
                    break;
                default:
                    // First turn
                    $this->initMovement(Module::SAMPLES);
                    $this->goToModule(Module::SAMPLES);
                    break;
            }
        }
    }

    function getSampleRanksSum()
    {
        return array_sum(array_values($this->askedSampleRanks));
    }

    function getSampleToProcess()
    {
        foreach ($this->currentSampleIds as $sampleId) {
            $sample = $this->samples[$sampleId];

            if ($sample->costA <= $this->availableA
                && $sample->costB <= $this->availableB
                && $sample->costC <= $this->availableC
                && $sample->costD <= $this->availableD
                && $sample->costE <= $this->availableE
            ) {
                error_log('Found one sample to make: ' . $sampleId);
                return $sampleId;
            }
        }

        return null;
    }

    function initMovement($module)
    {
        $this->inMovement = [
            'direction' => $module,
            'movementLeft' => $this->matrice[$this->target][$module]
        ];
    }

    function hasEnoughtMolecule()
    {
        $sample = $this->samples[$this->currentProcessingSampleId];

        error_log('Sample Id:' . $this->currentProcessingSampleId);
        error_log(var_export($this->samples, true));
        error_log($sample->log());
        if ($this->storageA < ($sample->costA - $this->expertiseA)) {
            error_log("Player has " . $this->storageA . " and need " . $sample->costA . " A molecules");
            if ($this->availableA == 0) {
                echo("WAIT\n");
            } else {
                echo("CONNECT A\n");
            }

            return false;
        } else if ($this->storageB < ($sample->costB - $this->expertiseB)) {
            error_log("Player has " . $this->storageB . " and need " . $sample->costB . " B molecules");
            if ($this->availableB == 0) {
                echo("WAIT\n");
            } else {
                echo("CONNECT B\n");
            }

            return false;
        } else if ($this->storageC < ($sample->costC - $this->expertiseC)) {
            error_log("Player has " . $this->storageC . " and need " . $sample->costC . " C molecules");
            if ($this->availableC == 0) {
                echo("WAIT\n");
            } else {
                echo("CONNECT C\n");
            }

            return false;
        } else if ($this->storageD < ($sample->costD - $this->expertiseD)) {
            error_log("Player has " . $this->storageD . " and need " . $sample->costD . " D molecules");
            if ($this->availableD == 0) {
                echo("WAIT\n");
            } else {
                echo("CONNECT D\n");
            }

            return false;
        } else if ($this->storageE < ($sample->costE - $this->expertiseE)) {
            error_log("Player has " . $this->storageE . " and need " . $sample->costE . " E molecules");
            if ($this->availableE == 0) {
                echo("WAIT\n");
            } else {
                echo("CONNECT E\n");
            }

            return false;
        }

        return true;
    }

    function searchSample()
    {
        error_log('Search samples');
        error_log('Current sample ids: ' . implode('-', $this->currentSampleIds));
        foreach ($this->samples as $sample) {
            if ($sample->carriedBy == 0 && !in_array($sample->sampleId, $this->currentSampleIds)) {
                error_log('Take Sample ' . $sample->sampleId);
                return $sample->sampleId;
            }
        }
    }
}