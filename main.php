<?php
define('CARRY_SAMPLES', 3);
define('CARRY_MOLECULES', 10);

/**
 * Bring data on patient samples from the diagnosis machine to the laboratory with enough molecules to produce medicine!
 **/

// game loop
$game = new Game();
$game->scanProjects();
while (TRUE)
{
    $game->scanPlayer($game->p1);
    $game->scanPlayer($game->p2);
    $game->p1->otherPlayer = $game->p2;

    $game->scanAvailableMolecules();
    $game->scanSamples();
    $game->next();
}







