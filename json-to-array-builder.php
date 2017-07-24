<?php

require_once __DIR__.'/lib/strings.php';

$className = 'CustomerFields';
$arrayName = 'fields';
$capArrayName = ucfirst($arrayName);
$json = <<<JS
{
    "id": 207119551,
    "email": "bob.norman@hostmail.com",
    "accepts_marketing": false,
    "created_at": "2017-05-26T14:06:54-04:00",
    "updated_at": "2017-05-26T14:06:54-04:00",
    "first_name": "Bob",
    "last_name": "Norman",
    "orders_count": 1,
    "state": "disabled",
    "total_spent": "41.94",
    "last_order_id": 450789469,
    "note": null,
    "verified_email": true,
    "multipass_identifier": null,
    "tax_exempt": false,
    "phone": null,
    "tags": "",
    "last_order_name": "#1001",
    "addresses": [
      {
        "id": 207119551,
        "first_name": null,
        "last_name": null,
        "company": null,
        "address1": "Chestnut Street 92",
        "address2": "",
        "city": "Louisville",
        "province": "Kentucky",
        "country": "United States",
        "zip": "40202",
        "phone": "555-625-1199",
        "name": "",
        "province_code": "KY",
        "country_code": "US",
        "country_name": "United States",
        "default": true
      }
    ],
    "default_address": {
      "id": 207119551,
      "first_name": null,
      "last_name": null,
      "company": null,
      "address1": "Chestnut Street 92",
      "address2": "",
      "city": "Louisville",
      "province": "Kentucky",
      "country": "United States",
      "zip": "40202",
      "phone": "555-625-1199",
      "name": "",
      "province_code": "KY",
      "country_code": "US",
      "country_name": "United States",
      "default": true
    }
}
JS;

$jsonArray = json_decode($json, true);

$propertiesTemplate = <<<'PT'
    const %capSnakeVarName% = '%snakeVarName%';
PT;

$witherTemplate = <<<'W'
    /**
     * @return %className%
     */
    public function with%capVariableName%(): %className%
    {
        $new = clone $this;
        $new->%arrayName%[] = self::%capSnakeVarName%;

        return $new;
    }

W;

$properties = [];
$withers = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun) = determineTypeVariables($value, $variableName);
    $capSnakeVarName = strtoupper($variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Prepare property
    $propertyPlaceHolders = ['%capSnakeVarName%', '%snakeVarName%'];
    $propertyReplacements = [$capSnakeVarName, $variableName];
    $properties[] = str_replace($propertyPlaceHolders, $propertyReplacements, $propertiesTemplate);

    // Prepare getter and setter
    $witherPlaceHolders = ['%className%', '%hintType%', '%type%', '%getVerb%', '%capVariableName%', '%variableName%', '%arrayName%', '%capSnakeVarName%'];
    $witherReplacements = [$className, $hintType, $type, $getVerb, $capVariableName, $camelName, $arrayName, $capSnakeVarName];
    $withers[] = str_replace($witherPlaceHolders, $witherReplacements, $witherTemplate);
}

$classTemplate = <<<'CT'
class %className%
{
%properties%

	/** @var string $%arrayName% */
    protected $%arrayName%;

    /**
     * %className% constructor.
     */
    public function __construct()
    {
        $this->%arrayName% = [];
    }

    /**
     * @return string[]
     */
    public function get%capArrayName%(): array
    {
        return array_unique($this->%arrayName%);
    }

%withers%
}
CT;

$classPlaceHolders = ['%className%', '%properties%', '%withers%', '%capArrayName%', '%arrayName%'];
$classReplacements = [$className, implode(PHP_EOL, $properties), implode(PHP_EOL, $withers), $capArrayName, $arrayName];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
