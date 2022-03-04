<?php

namespace NoCartorio\ArrayValidationByJson;

use DateTime;
use RuntimeException;

/**
 * Class Validator
 * @package NoCartorio\ArrayValidationByJson
 */
class Validator
{
    /**
     * @var mixed
     */
    protected $rules = [];

    protected $errors = [];

    protected $mainRule;

    /**
     * ExportJsonValidation constructor.
     */
    public function __construct(string $ruleset, $mainRule = 'base')
    {
        $this->mainRule = $mainRule;
        $this->rules = $this->loadRuleset($ruleset);
    }

    protected function prepareRun()
    {
        $this->errors = [];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array $items
     * @return bool
     */
    public function validate(array $items): bool
    {
        $this->prepareRun();

        return $this->validateItems($items, $this->rules[$this->mainRule]);
    }

    /**
     * @param array $items
     * @param array $baseValidator
     * @return bool
     */
    private function validateItems(array $items, array $baseValidator): bool
    {
        foreach ($baseValidator as $key => $value) {
            $keyValidation = $this->checkKey($key, $items);

            if (!$keyValidation) {
                $this->errors[] = "Section '$key' not found!";
                return false;
            }

            $validators = $this->getValidators($value);
            $itemValue = $items[$key];

            $valueType = gettype($itemValue);
            $hasNoContent = empty($itemValue);
            $couldBeNullable = array_key_exists('nullable', $validators);

            if ($hasNoContent && $couldBeNullable) {
                continue;
            }

            $isValueTypeAllowed = array_key_exists($valueType, $validators);
            $isFile = array_key_exists('file', $validators);

            if ($isValueTypeAllowed && !$isFile) {
                continue;
            }

            $couldBeInteger = array_key_exists('integer', $validators);
            $couldBeDouble = array_key_exists('double', $validators);
            $couldBeFloat = array_key_exists('float', $validators);
            $couldBeDate = array_key_exists('date', $validators);
            $couldBeDatetime = array_key_exists('datetime', $validators);
            $couldBeText = array_key_exists('text', $validators);

            if ($couldBeInteger || $couldBeDouble || $couldBeFloat) {
                $isNumeric = is_numeric($itemValue);
                if ($isNumeric) {
                    continue;
                }
            }

            if ($couldBeDate) {
                $isDate = $this->validateDate($itemValue, 'Y-m-d');
                if ($isDate) {
                    continue;
                }
            }

            if ($couldBeDatetime) {
                $isDate = $this->validateDate($itemValue, 'Y-m-d H:i:s');
                if ($isDate) {
                    continue;
                }
            }

            if ($couldBeText) {
                $isString = is_string($itemValue);
                if ($isString) {
                    continue;
                }
            }

            if ($isFile) {
                $file = $validators['file'];

                if (isset($this->rules[$file]) && is_array($this->rules[$file])) {
                    $fileBaseValidation = $this->rules[$file];
                    $isArray = array_key_exists('array', $validators);

                    if ($isArray) {
                        foreach ($itemValue as $item) {
                            $resultFile = $this->validateItems($item, $fileBaseValidation);

                            if (!$resultFile) {
                                return false;
                            }
                        }

                        continue;
                    }

                    $resultFile = $this->validateItems($itemValue, $fileBaseValidation);

                    if ($resultFile) {
                        continue;
                    }
                }
            }

            if (!is_string($itemValue)) {
                $itemValue = json_encode($itemValue);
            }

            $this->errors[] = "Validation failed for field '$key' with value '$itemValue'";
            return false;
        }

        return true;
    }

    /**
     * @param $key
     * @param $items
     * @return bool
     */
    private function checkKey($key, $items): bool
    {
        if (array_key_exists($key, $items)) {
            return true;
        }

        return false;
    }

    /**
     * @param $value
     * @return array
     */
    private function getValidators($value): array
    {
        $results = explode('|', $value);

        $validators = [];
        foreach ($results as $result) {
            $val = explode(',', $result);
            if (count($val) > 1) {
                $validators[$val[0]] = $val[1];
            } else {
                $validators[$val[0]] = $result;
            }
        }
        return $validators;
    }

    /**
     * @param $date
     * @param string $format
     * @return bool
     */
    private function validateDate($date, $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * @param string $ruleset
     * @return array
     */
    private function loadRuleset(string $ruleset): array
    {
        $validationData = [];
        $files = config('nocartorio-validate-rules-json.' . $ruleset);

        if (!$files) {
            throw new RuntimeException("There are no ruleset files to load for '$ruleset'!");
        }

        foreach ($files as $key => $fileURL) {
            $content = file_get_contents($fileURL);
            $validationData[$key] = json_decode($content, true);

            if (json_last_error()) {
                throw new RuntimeException("Failed to decode json data '{$content}' with error " . json_last_error_msg());
            }
        }

        return $validationData;
    }
}
