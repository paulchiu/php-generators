<?php

require_once __DIR__.'/lib/strings.php';

$className = 'Customer';
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
    "nullable_state": "disabled",
    "total_spent": "41.94",
    "nullable_last_order_id": 450789469,
    "note": null,
    "verified_email": true,
    "multipass_identifier": null,
    "tax_exempt": false,
    "phone": null,
    "tags": "",
    "nullable_last_order_name": "#1001",
    "addresses": "foo,bar",
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
    /** @var %hintType% $%variableName% */
    protected $%variableName%;
PT;

$getterSetterTemplate = <<<'GST'
    /**
     * @return %hintType%
     */
    public function %getVerb%%capVariableName%():? %type%
    {
        return $this->%variableName%;
    }

    /**
     * @param %hintType% $%variableName%
     * @return %className%
     */
    public function set%capVariableName%(%nullable%%type% $%variableName%): %className%
    {
        $this->%variableName% = $%variableName%;
        return $this;
    }

GST;

$properties = [];
$gettersAndSetters = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun, $nullable, $variableName) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Prepare property
    $propertyPlaceHolders = ['%hintType%', '%variableName%'];
    $propertyReplacements = [$hintType, $camelName];
    $properties[] = str_replace($propertyPlaceHolders, $propertyReplacements, $propertiesTemplate);

    // Prepare getter and setter
    $getterSetterPlaceHolders = ['%className%', '%hintType%', '%type%', '%getVerb%', '%capVariableName%', '%variableName%', '%nullable%'];
    $getterSetterReplacements = [$className, $hintType, $type, $getVerb, $capVariableName, $camelName, $nullable];
    $gettersAndSetters[] = str_replace($getterSetterPlaceHolders, $getterSetterReplacements, $getterSetterTemplate);
}

$classTemplate = <<<'CT'
class %className%
{
%properties%

%gettersAndSetters%
}
CT;

$classPlaceHolders = ['%className%', '%properties%', '%gettersAndSetters%'];
$classReplacements = [$className, implode(PHP_EOL, $properties), implode(PHP_EOL, $gettersAndSetters)];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
