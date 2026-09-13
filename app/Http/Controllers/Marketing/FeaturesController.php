<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class FeaturesController extends Controller
{
    public function index(): View
    {
        return view('marketing.features');
    }
}
