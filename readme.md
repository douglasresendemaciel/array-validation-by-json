# Validate Data For Import

This package is used to validate data exported to import on nocartorio.com from JSON files

## Installation

Run the following command from you terminal:

 ```bash
 composer require "nocartorio/validate-data-for-import"
 ```

or add this to require section in your composer.json file:

 ```
 "nocartorio/validate-data-for-import"
 ```

then run ```composer update```

Publishing config file

``` bash
set on config file generated the url´s with json rules for validation
```

Example of JSON files rules
```
{
"field-name": "string|nullable",
"other-field-name": "nullable|file,next-file-name-with-rules",
}
```
At the second casa, if rule is file you must set the next argument after comma, other JSON files with some structure for validation
``` bash
php artisan vendor:publish
```

## Usage

All allowed books for validation is: 'real-indicator', 'general-registry', 'general-registry'

``` php
$validated = new \NoCartorio\ArrayValidationByJson\Validator('real-indicator');
```
$validated could be true or false

## Author

Douglas Resende: [https://douglas.nocartorio.com/](https://douglas.nocartorio.com/)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
