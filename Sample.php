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

    function isCompleted($storageMolecules, $expertiseMolecules, $completedSampleMolecules)
    {
        return $this->costs['a'] <= $storageMolecules['a'] + $expertiseMolecules['a'] - $completedSampleMolecules['a']
            && $this->costs['b'] <= $storageMolecules['b'] + $expertiseMolecules['b'] - $completedSampleMolecules['b']
            && $this->costs['c'] <= $storageMolecules['c'] + $expertiseMolecules['c'] - $completedSampleMolecules['c']
            && $this->costs['d'] <= $storageMolecules['d'] + $expertiseMolecules['d'] - $completedSampleMolecules['d']
            && $this->costs['e'] <= $storageMolecules['e'] + $expertiseMolecules['e'] - $completedSampleMolecules['e'];
    }

    function canBeProduced($availableMolecules, $expertiseMolecules, $storageMolecules, $completedSampleMolecules)
    {
        $canBeProduced = $this->costs['a'] <= $expertiseMolecules['a'] - $completedSampleMolecules['a'] + $storageMolecules['a'] + $availableMolecules['a']
            && $this->costs['b'] <= $expertiseMolecules['b'] - $completedSampleMolecules['b'] + $storageMolecules['b'] + $availableMolecules['b']
            && $this->costs['c'] <= $expertiseMolecules['c'] - $completedSampleMolecules['c'] + $storageMolecules['c'] + $availableMolecules['c']
            && $this->costs['d'] <= $expertiseMolecules['d'] - $completedSampleMolecules['d'] + $storageMolecules['d'] + $availableMolecules['d']
            && $this->costs['e'] <= $expertiseMolecules['e'] - $completedSampleMolecules['e'] + $storageMolecules['e'] + $availableMolecules['e'];

        error_log("Sample " . $this->sampleId . ' ' . (($canBeProduced) ? 'can' : 'can\'t') . ' be produced');

        return $canBeProduced;
    }

    function getFirstMoleculeMissing($storageMolecules, $expertiseMolecules)
    {
        foreach ($this->costs as $molecule => $count) {
            if ($count > ($storageMolecules[$molecule] + $expertiseMolecules[$molecule])) {
                return strtoupper($molecule);
            }
        }

        return null;
    }
}