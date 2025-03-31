<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class JobFilterService
{
    protected Builder $query;
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->query = Job::query();
    }

    public function apply(): Builder
    {
        $this->applyBasicFilters()
             ->applyRelationshipFilters()
             ->applyAttributeFilters()
             ->applyLogicalOperators();

        return $this->query;
    }

    protected function applyBasicFilters(): self
    {
        $basicFields = $this->getBasicFields();

        foreach ($basicFields as $field => $operators) {
            if ($value = Arr::get($this->filters, $field)) {
                $this->applyFieldFilter($field, $value, $operators);
            }
        }

        return $this;
    }

    protected function applyRelationshipFilters(): self
    {
        if (isset($this->filters['AND']) || isset($this->filters['OR'])) {
            $this->applyConditions($this->filters, $this->query);
        } else {
            foreach ($this->getRelationships() as $relation) {
                if ($filter = Arr::get($this->filters, $relation)) {
                    $this->applyRelationFilter($relation, $filter);
                }
            }
        }

        return $this;
    }

    protected function applyAttributeFilters(): self
    {
        if ($attributes = Arr::get($this->filters, 'attributes')) {
            foreach ($attributes as $attribute => $condition) {
                $this->applyAttributeFilter($attribute, $condition);
            }
        }

        return $this;
    }

    protected function applyFieldFilter(string $field, $value, array $allowedOperators): void
    {
        if (is_array($value)) {
            $this->applyComplexFieldFilter($field, $value, $allowedOperators);
        } else {
            $this->query->where($field, '=', trim(trim($value, '"\'')));
        }
    }

    protected function applyComplexFieldFilter(string $field, array $value, array $allowedOperators): void
    {
        $operator = strtoupper($value[0]);
        $filterValue = $value[1];

        if (in_array($operator, $allowedOperators)) {
            if ($operator === 'LIKE') {
                $filterValue = "%{$filterValue}%";
            } elseif (in_array($operator, ['IN', 'HAS_ANY', 'IS_ANY'])) {
                $filterValue = array_map(fn($val) => trim(trim($val, '"\'')), (array)$filterValue);
                $this->query->whereIn($field, $filterValue);
                return;
            } elseif ($operator === '=') {
                $filterValue = trim(trim($filterValue, '"\''));
            }

            $this->query->where($field, $operator, $filterValue);
        }
    }

    protected function applyRelationFilter(string $relation, array $filter): void
    {
        $operator = strtoupper($filter[0]);
        $rawValues = array_map(fn($val) => trim(trim($val, '"\'')), (array)$filter[1]);
        $config = $this->getRelationConfig($relation);

        if (!$config) {
            throw new \InvalidArgumentException("Unknown relation: {$relation}");
        }

        switch ($operator) {
            case 'HAS_ANY':
            case 'IS_ANY':
                $this->applyHasAnyRelationFilter($config, $rawValues);
                break;
            case 'EXISTS':
                $this->query->whereHas($relation);
                break;
            case 'NOT_EXISTS':
                $this->query->whereDoesntHave($relation);
                break;
        }
    }

    protected function applyHasAnyRelationFilter(array $config, array $rawValues): void
    {
        $this->query->whereExists(function ($query) use ($config, $rawValues) {
            $baseQuery = $query->select(\DB::raw(1))
                ->from($config['table'])
                ->join($config['pivot'], "{$config['table']}.id", '=', "{$config['pivot']}.{$config['foreignKey']}")
                ->whereColumn("{$config['pivot']}.{$config['relatedKey']}", '=', 'jobs.id');

            $this->applyNameColumnFilters($baseQuery, $config, $rawValues);
        });
    }

    protected function applyNameColumnFilters($query, array $config, array $rawValues): void
    {
        $query->where(function ($q) use ($config, $rawValues) {
            $columns = $config['nameColumns'] ?? [$config['nameColumn']];
            foreach ($columns as $column) {
                foreach ($rawValues as $value) {
                    $q->orWhere("{$config['table']}.{$column}", 'LIKE', "%{$value}%");
                }
            }
        });
    }

    protected function applyAttributeFilter(string $attribute, array $condition): void
    {
        $operator = strtoupper($condition[0]);
        $value = is_numeric($condition[1]) ? (float)$condition[1] : "'" . trim(trim($condition[1], '"\'')) . "'";

        $this->query->whereHas('attributeValues', function ($query) use ($attribute, $operator, $value) {
            $query->whereHas('attribute', fn($q) => $q->where('attributes.name', $attribute))
                  ->whereRaw("attribute_values.value {$operator} {$value}");
        });
    }

    protected function applyLogicalOperators(): self
    {
        $this->applyConditions($this->filters, $this->query);
        return $this;
    }

    protected function applyConditions(array $conditions, Builder $query): void
    {

        foreach ($conditions as $logic => $subConditions) {
            if (is_numeric($logic)) {
                $this->applyCondition($subConditions, $query);
            } elseif (strtoupper($logic) === 'AND') {
                $query->where(fn($q) => $this->applyConditionsToQuery($subConditions, $q));
            } elseif (strtoupper($logic) === 'OR') {
                $query->orWhere(fn($q) => $this->applyConditionsToQuery($subConditions, $q));
            }
        }
    }

    protected function applyConditionsToQuery(array $conditions, Builder $query): void
    {
        foreach ($conditions as $condition) {
            $this->applyConditions($condition, $query);
        }
    }

    protected function applyCondition(array $condition, Builder $query): void
    {
        if (isset($condition['field'])) {
            $this->applyFieldFilter($condition['field'], [$condition['operator'], $condition['value']], ['=', '!=', 'LIKE', '>', '<', '>=', '<=']);
        } elseif (isset($condition['relationship'])) {
            $this->applyRelationFilter($condition['relationship'], [$condition['operator'], $condition['value']]);
        } elseif (isset($condition['attribute'])) {
            $this->applyAttributeFilter($condition['attribute'], [$condition['operator'], $condition['value']]);
        }
    }

    protected function getBasicFields(): array
    {
        return [
            'title' => ['=', '!=', 'LIKE'],
            'description' => ['=', '!=', 'LIKE'],
            'company_name' => ['=', '!=', 'LIKE'],
            'salary_min' => ['=', '!=', '>', '<', '>=', '<='],
            'salary_max' => ['=', '!=', '>', '<', '>=', '<='],
            'is_remote' => ['=', '!='],
            'job_type' => ['=', '!=', 'IN'],
            'status' => ['=', '!=', 'IN'],
            'published_at' => ['=', '!=', '>', '<', '>=', '<='],
        ];
    }

    protected function getRelationships(): array
    {
        return ['languages', 'locations', 'categories'];
    }

    protected function getRelationConfig(string $relation): ?array
    {
        return [
            'languages' => [
                'table' => 'languages',
                'pivot' => 'job_language',
                'foreignKey' => 'language_id',
                'relatedKey' => 'job_id',
                'nameColumn' => 'name',
            ],
            'locations' => [
                'table' => 'locations',
                'pivot' => 'job_location',
                'foreignKey' => 'location_id',
                'relatedKey' => 'job_id',
                'nameColumns' => ['city', 'state', 'country'],
            ],
            'categories' => [
                'table' => 'categories',
                'pivot' => 'job_category',
                'foreignKey' => 'category_id',
                'relatedKey' => 'job_id',
                'nameColumn' => 'name',
            ],
        ][$relation] ?? null;
    }
}