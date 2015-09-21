<?php
namespace Codeception\Util;

class JsonType
{
    protected $jsonArray;
    
    public function __construct($jsonArray)
    {
        if ($jsonArray instanceof JsonArray) {
            $jsonArray = $jsonArray->toArray();
        }
        $this->jsonArray = $jsonArray;
    }

    public function matches(array $jsonType)
    {
        $data = $this->jsonArray;
        if (array_key_exists(0, $this->jsonArray)) {
            // sequential array
            $data = reset($this->jsonArray);
        }
        return $this->typeComparison($data, $jsonType);
    }

    protected function typeComparison($data, $jsonType)
    {
        foreach ($jsonType as $key => $type) {
            if (!array_key_exists($key, $data)) {
                return "Key `$key` doesn't exist in " . json_encode($data);
            }
            if (is_array($jsonType[$key])) {
                $message = $this->typeComparison($data[$key], $jsonType[$key]);
                if (is_string($message)) {
                    return $message;
                }
                continue;
            }
            $matchTypes = explode('|', $type);
            $matched = false;
            foreach ($matchTypes as $matchType) {
                $currentType = strtolower(gettype($data[$key]));
                if ($currentType == 'double') {
                    $currentType = 'float';
                }
                $filters = explode(':', $matchType);
                $expectedType = trim(strtolower(array_shift($filters)));

                if ($expectedType != $currentType) {
                    break;
                }
                if (empty($filters)) {
                    $matched = true;
                    break;
                }
                foreach ($filters as $filter) {
                    $matched = $this->matchFilter($filter, $data[$key]);
                }
            }
            if (!$matched) {
                return sprintf("`$key: %s` is not of type `$type`", var_export($data[$key], true));
            }
        }
        return true;
    }

    protected function matchFilter($filter, $value)
    {
        $filter = trim($filter);
        if ($filter === 'url') {
            return preg_match('/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/', $value);
        }
        if (preg_match('~^regex\((.*?)\)$~', $filter, $matches)) {
            return preg_match($matches[1], $value);
        }
        if (preg_match('~^>([\d\.]+)$~', $filter, $matches)) {
            return (float)$value > (float)$matches[1];
        }
        if (preg_match('~^<([\d\.]+)$~', $filter, $matches)) {
            return (float)$value < (float)$matches[1];
        }

    }
}