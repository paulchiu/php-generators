<?php

require_once __DIR__.'/lib/strings.php';

$className = 'AccessTokenRequest';
$json = <<<JS
{
  "client_id": "foo",
  "client_secret": "bar",
  "code": "baz"
}
JS;

$jsonArray = json_decode($json, true);

$propertiesTemplate = <<<'PT'
    /** @var %hintType% $%variableName% */
    protected $%variableName%;
PT;

$witherTemplate = <<<'W'
    /**
     * @param %hintType% $%variableName%
     * @return %className%
     */
    public function with%capVariableName%(%type% $%variableName%): %className%
    {
        $new = clone $this;
        $new->%variableName% = $%variableName%;

        return $new;
    }

W;

$properties = [];
$withers = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Prepare property
    $propertyPlaceHolders = ['%hintType%', '%variableName%'];
    $propertyReplacements = [$hintType, $camelName];
    $properties[] = str_replace($propertyPlaceHolders, $propertyReplacements, $propertiesTemplate);

    // Prepare getter and setter
    $witherPlaceHolders = ['%className%', '%hintType%', '%type%', '%getVerb%', '%capVariableName%', '%variableName%'];
    $witherReplacements = [$className, $hintType, $type, $getVerb, $capVariableName, $camelName];
    $withers[] = str_replace($witherPlaceHolders, $witherReplacements, $witherTemplate);
}

$classTemplate = <<<'CT'
class %className%
{
%properties%

%withers%
}
CT;

$classPlaceHolders = ['%className%', '%properties%', '%withers%'];
$classReplacements = [$className, implode(PHP_EOL, $properties), implode(PHP_EOL, $withers)];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
