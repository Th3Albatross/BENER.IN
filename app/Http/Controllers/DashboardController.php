<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;

class DashboardController
{
    public function show() {
        $reports = Report::latest()->get();

        return view('dashboard.index', [
            '_reports' => $reports
        ]);
    }
}
