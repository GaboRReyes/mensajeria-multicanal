<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;

use Illuminate\Http\JsonResponse;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::orderBy('name')->get();
        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body'    => 'required|string',
            'channel' => 'required|in:email,whatsapp',
        ]);

        $template = Template::create($data);

        return response()->json($template, 201);
    }

    public function show($id)
    {
        $template = Template::findOrFail($id);
        return response()->json($template);
    }

    public function update(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $data = $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body'    => 'sometimes|required|string',
            'channel' => 'sometimes|required|in:email,whatsapp',
        ]);

        $template->update($data);

        return response()->json($template);
    }

    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        $template->delete();

        return response()->json(['deleted' => true]);
    }
}