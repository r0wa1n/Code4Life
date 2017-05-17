<?php

class Sample
{
    public $sampleId;
    public $carriedBy;
    public $rank;
    public $expertiseGain;
    public $health;
    public $costs;

    function log()
    {
        error_log("Player take a new sample: " . $this->sampleId);
        error_log("Sample need :");

        error_log("    A :" . $this->costs['a']);
        error_log("    B :" . $this->costs['b']);
        error_log("    C :" . $this->costs['c']);
        error_log("    D :" . $this->costs['d']);
        error_log("    E :" . $this->costs['e']);
    }

    function isCompleted($storageMolecules, $expertiseMolecules)
    {
        return $this->costs['a'] <= $storageMolecules['a'] + $expertiseMolecules['a']
            && $this->costs['b'] <= $storageMolecules['b'] + $expertiseMolecules['b']
            && $this->costs['c'] <= $storageMolecules['c'] + $expertiseMolecules['c']
            && $this->costs['d'] <= $storageMolecules['d'] + $expertiseMolecules['d']
            && $this->costs['e'] <= $storageMolecules['e'] + $expertiseMolecules['e'];
    }

    function canBeProduced($availableMolecules, $expertiseMolecules)
    {
        $canBeProduced = $this->costs['a'] <= $availableMolecules['a'] + $expertiseMolecules['a']
            && $this->costs['b'] <= $availableMolecules['b'] + $expertiseMolecules['b']
            && $this->costs['c'] <= $availableMolecules['c'] + $expertiseMolecules['c']
            && $this->costs['d'] <= $availableMolecules['d'] + $expertiseMolecules['d']
            && $this->costs['e'] <= $availableMolecules['e'] + $expertiseMolecules['e'];
        error_log("Sample " . $this->sampleId . ' ' . (($canBeProduced) ? 'can' : 'can\'t') . ' be produced');

        return $canBeProduced;
    }

    function getFirstMoleculeMissing($storageMolecules, $expertiseMolecules) {
        foreach ($this->costs as $molecule => $count) {
            error_log("Cost of molecule $molecule: $count");
            error_log("  My storage + expertise = " . ($storageMolecules[$molecule] + $expertiseMolecules[$molecule]));
            if($count > ($storageMolecules[$molecule] + $expertiseMolecules[$molecule])) {
                return strtoupper($molecule);
            }
        }
        return null;
    }
}