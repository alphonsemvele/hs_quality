<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class IndicateurController extends Controller
{
    public function index()
    {
        return Inertia::render('dashboard/indicateurs/index');
    }
}
