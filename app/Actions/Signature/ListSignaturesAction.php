<?php

namespace App\Actions\Signature;

use App\Models\Signature;
use Illuminate\Pagination\LengthAwarePaginator;

class ListSignaturesAction
{
    /**
     * List signatures for a user with pagination
     *
     * @param  array{user_id: string, per_page?: int, search?: string}  $data
     * @return LengthAwarePaginator<Signature>
     */
    public function execute(array $data): LengthAwarePaginator
    {
        $perPage = $data['per_page'] ?? 15;
        $search = $data['search'] ?? null;

        $query = Signature::where('user_id', $data['user_id'])
            ->orderBy('created_at', 'desc');

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }
}
