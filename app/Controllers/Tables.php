<?php

namespace App\Controllers;

class Tables extends BaseController
{
    public function index()
    {
        return view('tables/index', ['title' => 'Live floor plan — Salo', 'tables' => table_effective_statuses()]);
    }

    public function live()
    {
        return view('tables/_grid', ['tables' => table_effective_statuses()]);
    }
}
