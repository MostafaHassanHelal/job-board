<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'company_name',
        'salary_min',
        'salary_max',
        'is_remote',
        'job_type',
        'status',
        'published_at',
        'created_at',
        'updated_at',
    ];

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'job_language');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'job_location');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }
}
