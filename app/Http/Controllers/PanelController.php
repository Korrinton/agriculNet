<?php

namespace App\Http\Controllers;

use App\Services\PanelInicio;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    public function __invoke(Request $request, PanelInicio $panel)
    {
        return view('dashboard', $panel->para($request->user(), $request->integer('finca') ?: null, now()));
    }
}
