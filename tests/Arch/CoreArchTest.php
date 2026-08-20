<?php

arch('Core must not use App or Modules')
    ->expect('Core')
    ->not->toUse(['App', 'Modules']);

arch('Core non-UI must not use Filament')
    ->expect('Core')
    ->not->toUse('Filament');

arch('App must not use Modules')
    ->expect('App')
    ->not->toUse('Modules');

arch('Core must not use App\\Models')
    ->expect('Core')
    ->not->toUse('App\\Models');

arch('Core Security must not use App or Filament')
    ->expect('Core\Security')
    ->not->toUse(['App', 'Filament']);
