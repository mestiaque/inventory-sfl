<?php

namespace ME\SflInventory\Http\Controllers;

use Illuminate\Contracts\View\View;

class InvGuidelineController extends Controller
{
    public function index(): View
    {
        $this->authorize('inv_guideline.view');

        return view('sfl-inventory::admin.guideline.index');
    }
}
