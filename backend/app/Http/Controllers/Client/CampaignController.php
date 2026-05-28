<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $campaigns = Campaign::forUser($user->id)
            ->withCount('contacts')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'template_id'  => 'nullable|exists:templates,id',
            'channels'     => 'required|array|min:1',
            'channels.*'   => 'in:email,whatsapp',
            'variables'    => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
            'contact_ids'  => 'nullable|array',
            'contact_ids.*'=> 'exists:contacts,id',
        ]);

        $campaign = Campaign::create([
            'user_id'      => $user->id,
            'template_id'  => $data['template_id'] ?? null,
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'channels'     => $data['channels'],
            'variables'    => $data['variables'] ?? [],
            'status'       => isset($data['scheduled_at'])
                ? Campaign::STATUS_SCHEDULED
                : Campaign::STATUS_DRAFT,
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ]);

        // Agregar contactos si se proporcionaron
        if (! empty($data['contact_ids'])) {
            // Solo contactos del usuario
            $validIds = Contact::forUser($user->id)
                ->whereIn('id', $data['contact_ids'])
                ->pluck('id');

            $campaign->contacts()->attach($validIds);
            $campaign->update(['total_contacts' => $validIds->count()]);
        }

        return response()->json($campaign->load('contacts'), 201);
    }

    public function show(Request $request, string $uuid)
    {
        $campaign = Campaign::forUser($request->user()->id)
            ->with(['template', 'contacts'])
            ->findOrFail($uuid);

        return response()->json($campaign);
    }

    public function update(Request $request, string $uuid)
    {
        $campaign = Campaign::forUser($request->user()->id)
            ->whereIn('status', [Campaign::STATUS_DRAFT, Campaign::STATUS_SCHEDULED])
            ->findOrFail($uuid);

        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'template_id'  => 'nullable|exists:templates,id',
            'channels'     => 'sometimes|array|min:1',
            'channels.*'   => 'in:email,whatsapp',
            'variables'    => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $campaign->update($data);

        return response()->json($campaign);
    }

    /**
     * Enviar campaña: despacha el ProcessCampaignJob.
     */
    public function send(Request $request, string $uuid)
    {
        $user     = $request->user();
        $campaign = Campaign::forUser($user->id)
            ->with('contacts')
            ->findOrFail($uuid);

        if (! $campaign->canBeSent()) {
            return response()->json([
                'error'  => "La campaña no puede enviarse desde el estado '{$campaign->status}'.",
                'status' => $campaign->status,
            ], 422);
        }

        if ($campaign->contacts()->count() === 0) {
            return response()->json([
                'error' => 'Agrega al menos un contacto antes de enviar la campaña.',
            ], 422);
        }

        if (! $user->hasQuota()) {
            return response()->json([
                'error'          => 'Has alcanzado tu límite mensual de mensajes.',
                'monthly_limit'  => $user->monthly_limit,
                'used_this_month'=> $user->used_this_month,
            ], 429);
        }

        // Despachar el job de procesamiento
        ProcessCampaignJob::dispatch($campaign->id)->onQueue('campaigns');

        return response()->json([
            'message'     => 'Campaña encolada para procesamiento.',
            'campaign_id' => $campaign->id,
            'status'      => 'processing',
        ]);
    }

    /**
     * Agregar contactos a una campaña (en borrador).
     */
    public function addContacts(Request $request, string $uuid)
    {
        $user     = $request->user();
        $campaign = Campaign::forUser($user->id)
            ->whereIn('status', [Campaign::STATUS_DRAFT, Campaign::STATUS_SCHEDULED])
            ->findOrFail($uuid);

        $data = $request->validate([
            'contact_ids'   => 'required|array|min:1',
            'contact_ids.*' => 'exists:contacts,id',
            'variables'     => 'nullable|array', // variables globales para todos los contactos nuevos
        ]);

        $validIds = Contact::forUser($user->id)
            ->whereIn('id', $data['contact_ids'])
            ->pluck('id');

        $attachData = $validIds->mapWithKeys(fn ($id) => [
            $id => ['variables' => json_encode($data['variables'] ?? [])],
        ])->toArray();

        $campaign->contacts()->syncWithoutDetaching($attachData);
        $campaign->update(['total_contacts' => $campaign->contacts()->count()]);

        return response()->json([
            'attached' => $validIds->count(),
            'total'    => $campaign->total_contacts,
        ]);
    }

    public function destroy(Request $request, string $uuid)
    {
        $campaign = Campaign::forUser($request->user()->id)->findOrFail($uuid);

        if (! $campaign->canBeCancelled()) {
            $campaign->update(['status' => Campaign::STATUS_CANCELLED]);
        } else {
            $campaign->delete();
        }

        return response()->json(['deleted' => true]);
    }

    /**
     * Estadísticas detalladas de la campaña.
     */
    public function stats(Request $request, string $uuid)
    {
        $campaign = Campaign::forUser($request->user()->id)->findOrFail($uuid);

        $messageStats = $campaign->messages()
            ->select('status', \DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $channelStats = $campaign->messages()
            ->select('channel', \DB::raw('count(*) as total'))
            ->groupBy('channel')
            ->pluck('total', 'channel');

        return response()->json([
            'campaign'      => $campaign->only([
                'id', 'name', 'status', 'total_contacts', 'total_messages',
                'sent_count', 'delivered_count', 'failed_count',
                'started_at', 'completed_at',
            ]),
            'by_status'  => $messageStats,
            'by_channel' => $channelStats,
        ]);
    }
}
