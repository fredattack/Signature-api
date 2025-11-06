<?php

namespace App\Actions\Feedback;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ListFeedbackAction
{
    /**
     * List user's feedback with optional filtering
     *
     * @param  array{type?: string, status?: string, page?: int, per_page?: int}  $filters
     */
    public function execute(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = DB::table('feedback')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by type
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
