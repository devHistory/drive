<?php

namespace App\Http\Controllers\Console;


use App\Http\Controllers\Controller;

class IndexController extends Controller
{

    public function index()
    {
        return view('console/index');
    }

}
