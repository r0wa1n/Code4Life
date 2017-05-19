<?php

class Sample
{
    public $sampleId;
    public $carriedBy;
    public $rank;
    public $expertiseGain;
    public $health;
    public $costs;
    public $completed = false;

    function log()
    {
        error_log("Sample " . $this->sampleId . " need (health: " . $this->health . ") needs:");
        error_log("    A :" . $this->costs['a']);
        error_log("    B :" . $this->costs['b']);
        error_log("    C :" . $this->costs['c']);
        error_log("    D :" . $this->costs['d']);
        error_log("    E :" . $this->costs['e']);
    }

    function canBePushToLaboratory($storageMolecules)
    {
        return $this->costs['a'] <= $storageMolecules['a']
            && $this->costs['b'] <= $storageMolecules['b']
            && $this->costs['c'] <= $storageMolecules['c']
            && $this->costs['d'] <= $storageMolecules['d']
            && $this->costs['e'] <= $storageMolecules['e'];
    }

    function canBeProduced($availableMolecules, $storageMolecules, $slotsAvailable)
    {
        $canBeProduced = $this->costs['a'] <= $storageMolecules['a'] + $availableMolecules['a']
            && $this->costs['b'] <= $storageMolecules['b'] + $availableMolecules['b']
            && $this->costs['c'] <= $storageMolecules['c'] + $availableMolecules['c']
            && $this->costs['d'] <= $storageMolecules['d'] + $availableMolecules['d']
            && $this->costs['e'] <= $storageMolecules['e'] + $availableMolecules['e'];

        // Check available slots
        if ($canBeProduced) {
            $countWantedMolecules = max(0, $this->costs['a'] - $storageMolecules['a'])
                + max(0, $this->costs['b'] - $storageMolecules['b'])
                + max(0, $this->costs['c'] - $storageMolecules['c'])
                + max(0, $this->costs['d'] - $storageMolecules['d'])
                + max(0, $this->costs['e'] - $storageMolecules['e']);

            $canBeProduced &= $countWantedMolecules <= $slotsAvailable;
        }

        error_log("Sample " . $this->sampleId . ' ' . (($canBeProduced) ? 'can' : 'can\'t') . ' be produced');

        return $canBeProduced;
    }

    function getFirstMoleculeMissing($storageMolecules, Player $opponent = null)
    {
        $neededMolecules = [];
        foreach ($this->costs as $molecule => $count) {
            if ($count > $storageMolecules[$molecule]) {
                $neededMolecules[] = $molecule;
            }
        }

        if (empty($neededMolecules)) {
            return null;
        } else if (is_null($opponent)) {
            return strtoupper($neededMolecules[0]);
        } else {
            // Check if opponent need one of these molecules
            $opponentPriorityMolecule = $opponent->findWhichMoleculeTakeForSample(false);
            if (!is_null($opponentPriorityMolecule) && in_array($opponentPriorityMolecule, $neededMolecules)) {
                return strtoupper($opponentPriorityMolecule);
            } else {
                return strtoupper($neededMolecules[0]);
            }
        }
    }

    function timeToCompleteIt($storageMolecules) {
        $timeToFinishIt = 0;
        foreach ($this->costs as $molecule => $count) {
            if ($count > $storageMolecules[$molecule]) {
                $timeToFinishIt += $count - $storageMolecules[$molecule];
            }
        }
        return $timeToFinishIt;
    }
}