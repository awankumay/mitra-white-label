<?php

namespace App\Http\Controllers;

use Core\Context\Actions\SwitchUnitAction;
use Core\Exceptions\OrganizationException;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SwitchUnitController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $unitId = (string) $request->input('unit_id');

        if ($unitId === '') {
            Notification::make()
                ->title('Unit tidak dipilih.')
                ->danger()
                ->send();

            return redirect()->back();
        }

        try {
            app(SwitchUnitAction::class)->handle((string) $request->user()->id, $unitId);

            Notification::make()
                ->title('Unit berhasil diganti.')
                ->success()
                ->send();

            return redirect()->back();
        } catch (OrganizationException) {
            Notification::make()
                ->title('Anda tidak memiliki akses ke unit tersebut.')
                ->danger()
                ->send();

            return redirect()->back();
        }
    }
}
