<?php

namespace NoCartorio\ArrayValidationByJson;

use DateTime;
use Illuminate\Support\Facades\Log;

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
    public function __construct()
    {
        $this->base = $this->getValidatorData();
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

                $couldBeNullable = array_key_exists('nullable', $validators);
                $couldBeInteger = array_key_exists('integer', $validators);
                $couldBeDouble = array_key_exists('double', $validators);
                $couldBeFloat = array_key_exists('float', $validators);
                $couldBeDate = array_key_exists('date', $validators);
                $couldBeDatetime = array_key_exists('datetime', $validators);

                $valueType = gettype($itemValue);
                $hasNoContent = empty($itemValue);

                if ($hasNoContent && $couldBeNullable) {
                    continue;
                }

                $isValueTypeAllowed = array_key_exists($valueType, $validators);

                if (!$isValueTypeAllowed) {

                    if ($couldBeInteger || $couldBeDouble || $couldBeFloat) {
                        $isNumeric = is_numeric($itemValue);
                        if (!$isNumeric) {
                            Log::error("O item {$key} possui valor do tipo {$valueType} e este tipo não é permitido");
                            return false;
                        }
                    }

                    if ($couldBeDate) {
                        $isDate = $this->validateDate($itemValue, 'Y-m-d');
                        if (!$isDate) {
                            Log::error("O item {$key} possui valor do tipo {$valueType} e este tipo não é permitido");
                            return false;
                        }
                    }

                    if ($couldBeDatetime) {
                        $isDate = $this->validateDate($itemValue, 'Y-m-d H:i:s');
                        if (!$isDate) {
                            Log::error("O item {$key} possui valor do tipo {$valueType} e este tipo não é permitido");
                            return false;
                        }
                    }

                }

                $isFile = array_key_exists('file', $validators);
                if ($isFile) {
                    $file = $validators['file'];

                    if (isset($this->base[$file]) && is_array($this->base[$file])) {
                        $resultFile = self::validateItems($itemValue, $this->base[$file]);

                        if(!$resultFile){
                            return false;
                        }
                    }

                    return false;
                }
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
        if (isset($items[$key])) {
            return true;
        }

        Log::error('O array a ser validado não possui a chave ' . $key);

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
    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * @return array
     */
    private function getValidatorData(): array
    {
        $validationData = [];
        $files = config('nocartorio-validate-rules-json');

        foreach ($files as $key => $fileURL) {
            $content = file_get_contents($fileURL);
            $validationData[$key] = json_decode($content, true);
        }

        return $validationData;
    }
}
