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
        error_log("Sample need :");
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

    function canBeProduced($availableMolecules, $storageMolecules)
    {
        $canBeProduced = $this->costs['a'] <= $storageMolecules['a'] + $availableMolecules['a']
            && $this->costs['b'] <= $storageMolecules['b'] + $availableMolecules['b']
            && $this->costs['c'] <= $storageMolecules['c'] + $availableMolecules['c']
            && $this->costs['d'] <= $storageMolecules['d'] + $availableMolecules['d']
            && $this->costs['e'] <= $storageMolecules['e'] + $availableMolecules['e'];

        error_log("Sample " . $this->sampleId . ' ' . (($canBeProduced) ? 'can' : 'can\'t') . ' be produced');

        return $canBeProduced;
    }

    function getFirstMoleculeMissing($storageMolecules)
    {
        foreach ($this->costs as $molecule => $count) {
            if ($count > $storageMolecules[$molecule]) {
                return strtoupper($molecule);
            }
        }

        return null;
    }
}