<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class CommunicationController extends Controller
{
    public function index()
    {
        return Inertia::render('dashboard/communication/index');
    }
}
