<?php

$className = 'AccessToken';
$json = <<<JS
{
  "access_token": "f85632530bf277ec9ac6f649fc327f17",
  "scope": "write_orders,read_customers",
  "expires_in": 86399,
  "associated_user_scope": "write_orders,read_customers",
  "associated_user": {
    "id": 902541635,
    "first_name": "John",
    "last_name": "Smith",
    "email": "john@example.com",
    "account_owner": true
  }
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

function determineType($value, $key = null) {
    $hintType = $type = 'string';

    if (is_integer($value)) {
        $hintType = $type = 'int';
    } elseif (is_float($value)) {
        $hintType = $type = 'float';
    } elseif (is_string($value) && strpos($value, ',') !== false) {
        $arrayValues = array_map('trim', explode(',', $value));
        $arrayValueType = determineType($arrayValues[0])[0];
        $hintType = sprintf('array|%s[]', $arrayValueType);
        $type = 'array';
    } elseif (is_array($value)) {
        $hintType = $type = snakeToPascal($key);
    }

    return [$hintType, $type];
}

$getterSetterTemplate = <<<'GST'
    /**
     * @return %hintType%
     */
    public function get%capVariableName%():? %type%
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
    list($hintType, $type) = determineType($value, $variableName);
    $capVariableName = snakeToPascal($variableName);
    $camelName = snakeToCamel($variableName);

    // Prepare property
    $propertyPlaceHolders = ['%hintType%', '%variableName%'];
    $propertyReplacements = [$hintType, $camelName];
    $properties[] = str_replace($propertyPlaceHolders, $propertyReplacements, $propertiesTemplate);

    // Prepare getter and setter
    $getterSetterPlaceHolders = ['%className%', '%hintType%', '%type%', '%capVariableName%', '%variableName%'];
    $getterSetterReplacements = [$className, $hintType, $type, $capVariableName, $camelName];
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
