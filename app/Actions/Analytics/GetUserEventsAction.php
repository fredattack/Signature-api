<?php

namespace App\Actions\Analytics;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GetUserEventsAction
{
    /**
     * Get paginated user events
     *
     * @param  array{event_type?: string, start_date?: string, end_date?: string, page?: int, per_page?: int}  $filters
     */
    public function execute(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = DB::table('analytics_events')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by event type
        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        // Filter by date range
        if (! empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        // Paginate
        $perPage = $filters['per_page'] ?? 50;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
