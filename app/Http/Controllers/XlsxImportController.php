<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class XlsxImportController extends Controller
{
    public function index()
    {
        return view('xlsx.index');
    }

    public function upload(Request $request)
    {
        return redirect()->route('xlsx.results');
    }

    public function results()
    {
        return view('xlsx.results');
    }
}