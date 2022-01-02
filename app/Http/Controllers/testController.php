<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class testController extends Controller
{
    //
    protected function test1()
    {
        dd(now());
    }
}
