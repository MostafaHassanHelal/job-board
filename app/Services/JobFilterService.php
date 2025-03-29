<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Job;

class JobFilterService
{
    public static function apply(Request $request)
    {
        $query = Job::query();

        // Filtering by Title (Contains)
        if ($request->has('title')) {
            $query->where('title', 'LIKE', '%' . $request->title . '%');
        }

        // Filtering by Salary
        if ($request->has('salary_min')) {
            $query->where('salary_min', '>=', $request->salary_min);
        }
        if ($request->has('salary_max')) {
            $query->where('salary_max', '<=', $request->salary_max);
        }

        // Filtering by Job Type
        if ($request->has('job_type')) {
            $query->whereIn('job_type', explode(',', $request->job_type));
        }

        // Filtering by Status
        if ($request->has('status')) {
            $query->whereIn('status', explode(',', $request->status));
        }

        // Filtering by Remote Jobs
        if ($request->has('is_remote')) {
            $query->where('is_remote', $request->is_remote);
        }

        // Filtering by Languages (Many-to-Many)
        if ($request->has('languages')) {
            $query->whereHas('languages', function ($q) use ($request) {
                $q->whereIn('name', explode(',', $request->languages));
            });
        }

        // Filtering by Locations (Many-to-Many)
        if ($request->has('locations')) {
            $query->whereHas('locations', function ($q) use ($request) {
                $q->whereIn('city', explode(',', $request->locations));
            });
        }

        // Filtering by Categories (Many-to-Many)
        if ($request->has('categories')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->whereIn('name', explode(',', $request->categories));
            });
        }

        return $query->paginate(10);
    }
}
