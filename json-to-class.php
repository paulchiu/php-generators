<?php

$className = 'AssociatedUser';
$json = <<<JS
{
    "id": 902541635,
    "first_name": "John",
    "last_name": "Smith",
    "email": "john@example.com",
    "account_owner": true
}
JS;

$jsonArray = json_decode($json, true);

$propertiesTemplate = <<<'PT'
    /** @var %hintType% $%variableName% */
    protected $%variableName%;
PT;

function snakeToPascal($string) {
    return str_replace('_', '', ucwords($string, '_'));
}

function snakeToCamel($string) {
    return lcfirst(snakeToPascal($string));
}

function determineTypeVariables($value, $key = null) {
    $hintType = $type = 'string';
    $getVerb = 'get';

    if (is_integer($value)) {
        $hintType = $type = 'int';
    } elseif (is_float($value)) {
        $hintType = $type = 'float';
    } elseif (is_bool($value)) {
        $hintType = $type = 'bool';
        $getVerb = 'is';
    } elseif (is_string($value) && strpos($value, ',') !== false) {
        $arrayValues = array_map('trim', explode(',', $value));
        $arrayValueType = determineTypeVariables($arrayValues[0])[0];
        $hintType = sprintf('array|%s[]', $arrayValueType);
        $type = 'array';
    } elseif (is_array($value)) {
        $hintType = $type = snakeToPascal($key);
    }

    return [$hintType, $type, $getVerb];
}

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
    public function set%capVariableName%(%type% $%variableName%): %className%
    {
        $this->%variableName% = $%variableName%;
        return $this;
    }

GST;

$properties = [];
$gettersAndSetters = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($variableName);
    $camelName = snakeToCamel($variableName);

    // Prepare property
    $propertyPlaceHolders = ['%hintType%', '%variableName%'];
    $propertyReplacements = [$hintType, $camelName];
    $properties[] = str_replace($propertyPlaceHolders, $propertyReplacements, $propertiesTemplate);

    // Prepare getter and setter
    $getterSetterPlaceHolders = ['%className%', '%hintType%', '%type%', '%getVerb%', '%capVariableName%', '%variableName%'];
    $getterSetterReplacements = [$className, $hintType, $type, $getVerb, $capVariableName, $camelName];
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
