<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Admins ven todos; clientes/developers ven los suyos + globales
        $templates = $user->isAdmin()
            ? Template::with('user:id,name')->orderBy('name')->paginate(50)
            : Template::forUser($user->id)->orderBy('name')->get();

        return response()->json($templates);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'subject'               => 'nullable|string|max:255',
            'body'                  => 'required|string',
            'channel'               => 'required|in:email,whatsapp',
            'whatsapp_template_name'=> 'nullable|string',
            'language'              => 'nullable|string|max:10',
            'variables'             => 'nullable|array',
            'is_active'             => 'nullable|boolean',
        ]);

        $template = Template::create([
            ...$data,
            'user_id' => $user->isAdmin() ? null : $user->id, // admins crean templates globales
        ]);

        return response()->json($template, 201);
    }

    public function show(Request $request, int $id)
    {
        $user     = $request->user();
        $template = $user->isAdmin()
            ? Template::findOrFail($id)
            : Template::forUser($user->id)->findOrFail($id);

        return response()->json($template);
    }

    public function update(Request $request, int $id)
    {
        $user     = $request->user();
        $template = $user->isAdmin()
            ? Template::findOrFail($id)
            : Template::where('user_id', $user->id)->findOrFail($id); // solo los propios

        $data = $request->validate([
            'name'                  => 'sometimes|string|max:255',
            'subject'               => 'nullable|string|max:255',
            'body'                  => 'sometimes|string',
            'channel'               => 'sometimes|in:email,whatsapp',
            'whatsapp_template_name'=> 'nullable|string',
            'language'              => 'nullable|string|max:10',
            'variables'             => 'nullable|array',
            'is_active'             => 'nullable|boolean',
        ]);

        $template->update($data);
        return response()->json($template);
    }

    public function destroy(Request $request, int $id)
    {
        $user     = $request->user();
        $template = $user->isAdmin()
            ? Template::findOrFail($id)
            : Template::where('user_id', $user->id)->findOrFail($id);

        $template->delete();
        return response()->json(['deleted' => true]);
    }
}
