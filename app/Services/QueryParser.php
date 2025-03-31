<?php

namespace App\Services;

use InvalidArgumentException;

class QueryParser {
    private $queryString;
    private $parsedQuery;

    public function __construct(string $queryString) {
        $this->queryString = $queryString;
        $this->parsedQuery = [];
    }

    public function parse() {
        $filterExpression = $this->queryString;
        if (str_starts_with($filterExpression, 'filter=')) {
            $filterExpression = substr($filterExpression, 7);
        }
        $filterExpression = trim($filterExpression);
        if (empty($filterExpression)) {
            return [];
        }
        
        $result = $this->parseFilterExpression($filterExpression);
        
        return $result;
    }

    private function parseFilterExpression($expression) {
        $conditions = [];
        $current = '';
        $depth = 0;
        $length = strlen($expression);
        $bracketContent = '';
        
        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];
            
            if (in_array($char, ['(', '[', '{'])) {
                if ($depth === 0) {
                    $bracketContent = '';
                } else {
                    $bracketContent .= $char;
                }
                $depth++;
            }
            elseif (in_array($char, [')', ']', '}'])) {
                $depth--;
                if ($depth > 0) {
                    $bracketContent .= $char;
                } else {
                    if (!empty($bracketContent)) {
                        if (stripos($bracketContent, ' AND ') !== false || stripos($bracketContent, ' OR ') !== false) {
                            $conditions[] = $this->parseFilterExpression($bracketContent);
                        } else {
                            $conditions[] = $this->parseCondition($bracketContent);
                        }
                    }
                    $bracketContent = '';
                }
            }
            elseif ($depth > 0) {
                $bracketContent .= $char;
            }
            else {
                if ($char === ' ') {
                    $word = trim($current);
                    if (!empty($word)) {
                        if (in_array(strtoupper($word), ['AND', 'OR'])) {
                            $conditions[] = ['operator' => strtoupper($word)];
                        } else {
                            $this->addCondition($conditions, $word);
                        }
                        $current = '';
                    }
                } else {
                    $current .= $char;
                }
            }
        }
        
        $word = trim($current);
        if (!empty($word)) {
            if (in_array(strtoupper($word), ['AND', 'OR'])) {
                $conditions[] = ['operator' => strtoupper($word)];
            } else {
                $this->addCondition($conditions, $word);
            }
        }

        return $conditions;
    }

    private function addCondition(&$conditions, $condition) {
        if (!empty($condition)) {
            if (in_array(strtoupper($condition), ['AND', 'OR'])) {
                $conditions[] = ['operator' => strtoupper($condition)];
            } else {
                if (preg_match('/^[({\[](.+)[)}\]]$/', $condition, $matches)) {
                    $inner = trim($matches[1]);
                    if (stripos($inner, ' AND ') !== false || stripos($inner, ' OR ') !== false) {
                        $conditions[] = $this->parseFilterExpression($inner);
                    } else {
                        $conditions[] = $this->parseCondition($inner);
                    }
                } else {
                    $conditions[] = $this->parseCondition($condition);
                }
            }
        }
    }

    private function parseCondition($condition) {
        \Log::info('Parsing condition', [
            'condition' => $condition
        ]);

        if (strpos($condition, 'HAS_ANY') !== false) {
            if (preg_match('/(\w+)\s+HAS_ANY\s*\((.*?)\)/', $condition, $matches)) {
                return [
                    'field' => $matches[1],
                    'operator' => 'HAS_ANY',
                    'value' => array_map('trim', explode(',', $matches[2]))
                ];
            }
        }
        
        if (strpos($condition, 'IS_ANY') !== false) {
            if (preg_match('/(\w+)\s+IS_ANY\s*\((.*?)\)/', $condition, $matches)) {
                return [
                    'field' => $matches[1],
                    'operator' => 'IS_ANY',
                    'value' => array_map('trim', explode(',', $matches[2]))
                ];
            }
        }
        
        if (preg_match('/^(\w+:)?(\w+)(>=|<=|=|>|<)(.+)$/', $condition, $matches)) {
            return [
                'prefix' => rtrim($matches[1], ':') ?: null,
                'field' => $matches[2],
                'operator' => $matches[3],
                'value' => trim($matches[4])
            ];
        }

        throw new InvalidArgumentException("Invalid condition format: $condition");
    }
}