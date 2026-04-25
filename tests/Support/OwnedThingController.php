<?php

namespace Tests\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerController;

class OwnedThingController extends Controller
{
    use AuthorizesOwnerController;

    public function index(Request $request): JsonResponse
    {
        $query = OwnedThing::query();
        $query = $this->scopeAccessibleOwners($query, $request->user());

        return response()->json([
            'data' => $query->get()->all(),
        ]);
    }

    public function store(StoreOwnedThingRequest $request): JsonResponse
    {
        $thing = OwnedThing::create($request->validated());

        return response()->json(['data' => $thing], 201);
    }

    public function show(OwnedThing $thing, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($thing, $request->user());

        return response()->json(['data' => $thing]);
    }

    public function update(UpdateOwnedThingRequest $request, OwnedThing $thing): JsonResponse
    {
        $thing->update($request->validated());

        return response()->json(['data' => $thing]);
    }

    public function destroy(OwnedThing $thing, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($thing, $request->user());
        $thing->delete();

        return response()->json(['ok' => true]);
    }
}
