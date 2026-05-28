<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $contacts = Contact::forUser($user->id)
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('email', 'ilike', "%{$request->search}%")
                  ->orWhere('phone', 'ilike', "%{$request->search}%");
            }))
            ->when($request->tag, fn ($q) => $q->whereJsonContains('tags', $request->tag))
            ->when($request->active !== null, fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate(50);

        return response()->json($contacts);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email',
            'phone'    => 'nullable|string|max:20',
            'metadata' => 'nullable|array',
            'tags'     => 'nullable|array',
            'tags.*'   => 'string|max:50',
            'is_active'=> 'nullable|boolean',
        ]);

        if (empty($data['email']) && empty($data['phone'])) {
            return response()->json([
                'error' => 'El contacto debe tener al menos email o teléfono.',
            ], 422);
        }

        $contact = Contact::create([...$data, 'user_id' => $user->id]);

        return response()->json($contact, 201);
    }

    public function show(Request $request, string $uuid)
    {
        $contact = Contact::forUser($request->user()->id)->findOrFail($uuid);
        return response()->json($contact);
    }

    public function update(Request $request, string $uuid)
    {
        $contact = Contact::forUser($request->user()->id)->findOrFail($uuid);

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'nullable|email',
            'phone'    => 'nullable|string|max:20',
            'metadata' => 'nullable|array',
            'tags'     => 'nullable|array',
            'tags.*'   => 'string|max:50',
            'is_active'=> 'nullable|boolean',
        ]);

        $contact->update($data);
        return response()->json($contact);
    }

    public function destroy(Request $request, string $uuid)
    {
        $contact = Contact::forUser($request->user()->id)->findOrFail($uuid);
        $contact->delete();
        return response()->json(['deleted' => true]);
    }

    /**
     * Importación masiva de contactos desde JSON.
     * Formato: [{"name":"...","email":"...","phone":"...","tags":["tag1"]}]
     */
    public function import(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'contacts'       => 'required|array|min:1|max:5000',
            'contacts.*.name'=> 'required|string|max:255',
        ]);

        $created = 0;
        $errors  = [];

        foreach ($request->contacts as $index => $row) {
            $v = Validator::make($row, [
                'name'  => 'required|string|max:255',
                'email' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
                'tags'  => 'nullable|array',
            ]);

            if ($v->fails()) {
                $errors[$index] = $v->errors()->all();
                continue;
            }

            if (empty($row['email']) && empty($row['phone'])) {
                $errors[$index] = ['Debe tener email o teléfono.'];
                continue;
            }

            Contact::create([
                'user_id'   => $user->id,
                'name'      => $row['name'],
                'email'     => $row['email'] ?? null,
                'phone'     => $row['phone'] ?? null,
                'tags'      => $row['tags'] ?? [],
                'metadata'  => $row['metadata'] ?? [],
                'is_active' => true,
            ]);

            $created++;
        }

        return response()->json([
            'created' => $created,
            'errors'  => $errors,
            'total'   => count($request->contacts),
        ], 207);
    }
}
