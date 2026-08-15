<?php

arch('Core must not use App or Modules')
    ->expect('Core')
    ->not->toUse(['App', 'Modules']);

arch('Core non-UI must not use Filament')
    ->expect('Core')
    ->not->toUse('Filament');
