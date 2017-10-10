<?php

require_once __DIR__.'/lib/strings.php';

$className = 'MetafieldFields';
$arrayName = 'fields';
$capArrayName = ucfirst($arrayName);
$json = <<<JS
{
      "id": 915396087,
      "namespace": "inventory",
      "key": "warehouse",
      "value": 25,
      "value_type": "integer",
      "description": null,
      "owner_id": 690933842,
      "created_at": "2017-03-07T17:16:51-05:00",
      "updated_at": "2017-03-07T17:16:51-05:00",
      "owner_resource": "shop"
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
