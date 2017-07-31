<?php

require_once __DIR__.'/lib/strings.php';

$className = 'CustomerInvite';
$json = <<<JS
{
    "to": "new_test_email@shopify.com",
    "from": "noaccesssteve@jobs.com",
    "array_bcc": [
      "noaccesssteve@jobs.com"
    ],
    "subject": "Welcome to my new shop",
    "custom_message": "My awesome new store"
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
