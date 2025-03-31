<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\JobFilterService;
use App\Services\QueryParser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filterString = $request->input('filter', '');
        $filters = $this->parseFilterString($filterString);
        $jobs = (new JobFilterService($filters))
            ->apply()
            ->with(['languages:name', 'locations:country,city,state', 'categories:name', 'attributeValues.attribute'])
            ->paginate($request->input('per_page', 15));

        return response()->json($jobs);
    }

    /**
     * Parse the filter string into a structured array.
     *
     * @param string $filterString
     * @return array
     */
    private function parseFilterString(string $filterString): array
    {
        if (empty($filterString)) {
            return [];
        }

        try {
            //$filterString = $this->normalizeFilterString($filterString);
            $parser = new QueryParser($filterString);
            $parsedConditions = $parser->parse();
            $result = $this->convertParsedConditions($parsedConditions);


            return $result;
        } catch (InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * Normalize the filter string by ensuring it has the correct format.
     *
     * @param string $filterString
     * @return string
     */
    private function normalizeFilterString(string $filterString): string
    {
        if (!str_starts_with($filterString, 'filter=')) {
            $filterString = 'filter=(' . trim($filterString, '()') . ')';
        }

        return $filterString;
    }

    /**
     * Convert parsed conditions into the format expected by JobFilterService.
     *
     * @param array $conditions
     * @return array
     */
    private function convertParsedConditions(array $conditions): array
    {
        $result = [];

        foreach ($conditions as $condition) {
            if ($this->isNestedCondition($condition)) {
                $this->mergeNestedConditions($result, $condition);
                continue;
            }

            if ($this->isLogicalOperator($condition)) {
                continue;
            }

            $this->processCondition($result, $condition);
        }

        return $result;
    }

    /**
     * Check if the condition is a nested condition.
     *
     * @param array $condition
     * @return bool
     */
    private function isNestedCondition(array $condition): bool
    {
        return is_array($condition) && !isset($condition['field']) && !isset($condition['operator']);
    }

    /**
     * Merge nested conditions into the result array.
     *
     * @param array $result
     * @param array $condition
     * @return void
     */
    private function mergeNestedConditions(array &$result, array $condition): void
    {
        $nestedResult = $this->convertParsedConditions($condition);
        foreach ($nestedResult as $key => $value) {
            if (!isset($result[$key])) {
                $result[$key] = $value;
            }
        }
    }

    /**
     * Check if the condition is a logical operator (AND/OR).
     *
     * @param array $condition
     * @return bool
     */
    private function isLogicalOperator(array $condition): bool
    {
        return isset($condition['operator']) && in_array($condition['operator'], ['AND', 'OR']);
    }

    /**
     * Process a single condition and add it to the result array.
     *
     * @param array $result
     * @param array $condition
     * @return void
     */
    private function processCondition(array &$result, array $condition): void
    {
        if (isset($condition['prefix']) && $condition['prefix'] === 'attribute') {
            $this->addAttributeCondition($result, $condition);
        } elseif (isset($condition['operator']) && in_array($condition['operator'], ['HAS_ANY', 'IS_ANY'])) {
            $this->addRelationshipCondition($result, $condition);
        } else {
            $this->addBasicFieldCondition($result, $condition);
        }
    }

    /**
     * Add an attribute condition to the result array.
     *
     * @param array $result
     * @param array $condition
     * @return void
     */
    private function addAttributeCondition(array &$result, array $condition): void
    {
        $result['attributes'][$condition['field']] = [
            $condition['operator'],
            trim($condition['value']),
        ];
    }

    /**
     * Add a relationship condition to the result array.
     *
     * @param array $result
     * @param array $condition
     * @return void
     */
    private function addRelationshipCondition(array &$result, array $condition): void
    {
        $result[$condition['field']] = [
            $condition['operator'],
            $condition['value'],
        ];
    }

    /**
     * Add a basic field condition to the result array.
     *
     * @param array $result
     * @param array $condition
     * @return void
     */
    private function addBasicFieldCondition(array &$result, array $condition): void
    {
        $result[$condition['field']] = [
            $condition['operator'],
            trim($condition['value']),
        ];
    }
}