<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobFilterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'company_name' => 'nullable|string',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'is_remote' => 'nullable|boolean',
            'job_type' => 'nullable|array',
            'status' => 'nullable|array',
            'published_at' => 'nullable|date',
            'created_at' => 'nullable|date',
            'languages' => 'nullable|array',
            'locations' => 'nullable|array',
            'categories' => 'nullable|array',
            'attribute:*' => 'nullable|string',
        ];
    }
}