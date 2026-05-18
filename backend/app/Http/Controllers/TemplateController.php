<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }

    public function store(Request $request)
    {
        return response()->json(['created' => true]);
    }

    public function show($id)
    {
        return response()->json(['id' => $id]);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['updated' => $id]);
    }

    public function destroy($id)
    {
        return response()->json(['deleted' => $id]);
    }
}