<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Address;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final readonly class AddressService
{
    public function list(string $userId): Collection
    {
        return Address::where('user_id', $userId)->get();
    }

    public function store(string $userId, array $data): Address
    {
        Profile::findOrFail($userId);

        if (!empty($data['is_default'])) {
            Address::where('user_id', $userId)->update(['is_default' => false]);
        }

        return Address::create([
            ...$data,
            'id'      => Str::uuid()->toString(),
            'user_id' => $userId,
        ]);
    }

    public function update(string $userId, string $addressId, array $data): Address
    {
        $address = Address::where('id', $addressId)
            ->where('user_id', $userId)
            ->firstOrFail();

        if (!empty($data['is_default'])) {
            Address::where('user_id', $userId)
                ->where('id', '!=', $addressId)
                ->update(['is_default' => false]);
        }

        $address->update($data);

        return $address->fresh();
    }

    public function delete(string $userId, string $addressId): void
    {
        Address::where('id', $addressId)
            ->where('user_id', $userId)
            ->firstOrFail()
            ->delete();
    }
}