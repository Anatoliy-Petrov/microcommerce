<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addressService) {}

    public function index(Request $request, string $id): JsonResponse
    {
        return $this->success(AddressResource::collection($this->addressService->list($id)));
    }

    public function store(StoreAddressRequest $request, string $id): JsonResponse
    {
        $address = $this->addressService->store($id, $request->validated());

        return $this->success(new AddressResource($address), 201);
    }

    public function update(UpdateAddressRequest $request, string $id, string $addrId): JsonResponse
    {
        $address = $this->addressService->update($id, $addrId, $request->validated());

        return $this->success(new AddressResource($address));
    }

    public function destroy(Request $request, string $id, string $addrId): JsonResponse
    {
        $this->addressService->delete($id, $addrId);

        return $this->success(null, 204);
    }
}
