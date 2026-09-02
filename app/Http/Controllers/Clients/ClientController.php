<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $clients = Client::query()
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                );
            })
            ->withCount('invoices')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => ['search' => $request->string('search')->toString()],
            'can' => ['create' => $request->user()->can('create', Client::class)],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('Clients/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $validated = $this->validated($request);

        Client::create($validated);

        return redirect()->route('clients.index')->with('status', 'Client added.');
    }

    public function show(Request $request, Client $client): Response
    {
        $this->authorize('view', $client);

        $client->load(['invoices' => fn ($query) => $query->latest('issue_date')]);

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'can' => [
                'update' => $request->user()->can('update', $client),
                'delete' => $request->user()->can('delete', $client),
            ],
        ]);
    }

    public function edit(Request $request, Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('Clients/Edit', ['client' => $client]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($this->validated($request, $client));

        return redirect()->route('clients.show', $client)->with('status', 'Client updated.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Client deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Client $client = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'tax_id' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
