<?php

namespace NoCartorio\ArrayValidationByJson;

use DateTime;

/**
 * Class Validator
 * @package NoCartorio\ArrayValidationByJson
 */
class Validator
{
    /**
     * @var mixed
     */
    private $base = [];

    /**
     * ExportJsonValidation constructor.
     */
    public function __construct(string $validatorKey)
    {
        $this->base = $this->getValidatorData($validatorKey);
    }

    /**
     * @param array $items
     * @return bool
     */
    public function validate(array $items): bool
    {
        return $this->validateItems($items, $this->base['base']);
    }

    /**
     * @param array $items
     * @param array $baseValidator
     * @return bool
     */
    private function validateItems(array $items, array $baseValidator): bool
    {
        foreach ($baseValidator as $key => $value) {
            $keyValidation = self::checkKey($key, $items);
            if ($keyValidation) {
                $validators = self::getValidators($value);
                $itemValue = $items[$key];

                $valueType = gettype($itemValue);
                $hasNoContent = empty($itemValue);
                $couldBeNullable = array_key_exists('nullable', $validators);

                if ($hasNoContent && $couldBeNullable) {
                    continue;
                }

                $isValueTypeAllowed = array_key_exists($valueType, $validators);

                if ($isValueTypeAllowed) {
                    continue;
                }

                $couldBeInteger = array_key_exists('integer', $validators);
                $couldBeDouble = array_key_exists('double', $validators);
                $couldBeFloat = array_key_exists('float', $validators);
                $couldBeDate = array_key_exists('date', $validators);
                $couldBeDatetime = array_key_exists('datetime', $validators);
                $couldBeText = array_key_exists('text', $validators);
                $isFile = array_key_exists('file', $validators);
                
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

                    if (isset($this->base[$file]) && is_array($this->base[$file])) {
                        $fileBaseValidation = $this->base[$file];
                        $resultFile = self::validateItems($itemValue, $fileBaseValidation);

                        if ($resultFile) {
                            continue;
                        }
                    }
                }
                return false;
            }
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
     * @param string $validatorKey
     * @return array
     */
    private function getValidatorData(string $validatorKey): array
    {
        $validationData = [];
        $files = config('nocartorio-validate-rules-json.' . $validatorKey);

        foreach ($files as $key => $fileURL) {
            $content = file_get_contents($fileURL);
            $validationData[$key] = json_decode($content, true);
        }

        return $validationData;
    }
}
