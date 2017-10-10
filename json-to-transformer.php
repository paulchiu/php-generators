<?php

require_once __DIR__.'/lib/strings.php';
require_once __DIR__.'/lib/typed-templates.php';

$className = 'Redirect';
$unwrap = true;
$acceptsArrayResponse = true;
$classVariableName = lcfirst($className);
$snakeClassName = pascalToSnake($className);
$json = <<<JS
{
    "id": 668809255,
    "path": "\/leopard",
    "target": "\/pages\/macosx"
}
JS;

$jsonArray = json_decode($json, true);

$dependencyTemplate = <<<'DT'
    /** @var %capVariableName% $%camelName%Transformer */
    protected $%camelName%Transformer;

DT;

$unwrapResponseTemplate = <<<'UT'
    /**
     * @param ResponseInterface $response
     * @return %className%Model
     * @throws MissingExpectedAttributeException
     */
    public function fromResponse(ResponseInterface $response): %className%Model
    {
        $stdClass = json_decode($response->getBody()->getContents());

        if (!property_exists($stdClass, '%snakeClassName%')) {
            throw new MissingExpectedAttributeException('%snakeClassName%');
        }

        return $this->fromShopifyJson%className%($stdClass->%snakeClassName%);
    }
    
UT;

$arrayResponseTemplate = <<<'ART'
    /**
     * @param ResponseInterface $response
     * @return array|%className%Model[]
     * @throws MissingExpectedAttributeException
     */
    public function fromArrayResponse(ResponseInterface $response): array
    {
        $stdClass = json_decode($response->getBody()->getContents());

        if (!property_exists($stdClass, '%snakeClassName%')) {
            throw new MissingExpectedAttributeException('%snakeClassName%');
        }

        return array_map([$this, 'fromShopifyJson%className%'], $stdClass->%snakeClassName%);
    }
    
ART;

$responseTemplate = <<<'RT'
    /**
     * @param ResponseInterface $response
     * @return %className%Model
     */
    public function fromResponse(ResponseInterface $response): %className%Model
    {
        $stdClass = json_decode($response->getBody()->getContents());
        return $this->fromShopifyJson%className%($stdClass);
    }
RT;


$dependencies = [];
$propertyAssignments = [];
$arrayAssignments = [];
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun, $nullable, $variableName) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Determine templates
    $propertyTemplate = getPropertyAssignmentTemplate($type);
    $arrayTemplate = getObjectArrayAssignmentTemplate($type, $hintType);

    // Prepare array assignments
    $assignmentPlaceHolders = ['%className%', '%classVariableName%', '%variableName%', '%capVariableName%', '%camelName%', '%getVerb%'];
    $assignmentReplacements = [$className, $classVariableName, $variableName, $capVariableName, $camelName, $getVerb];

    // Do replacement
    $propertyAssignments[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $propertyTemplate);
    $arrayAssignments[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $arrayTemplate);

    // Do object templates
    if (is_array($value)) {
        $dependencies[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $dependencyTemplate);
    }
}

$responseTemplate = ($unwrap) ? $unwrapResponseTemplate : $responseTemplate;
$responsePlaceHolders = ['%className%', '%snakeClassName%'];
$responseReplacements = [$className, $snakeClassName];
$responseParser = str_replace($responsePlaceHolders, $responseReplacements, $responseTemplate);

$arrayResponseParser = '';
$arrayResponseParserInterface = '';
$arrayResponseParserInterfaceImport = '';
if ($acceptsArrayResponse) {
    $responsePlaceHolders = ['%className%', '%snakeClassName%'];
    $responseReplacements = [$className, pluralize($snakeClassName)];
    $arrayResponseParser = str_replace($responsePlaceHolders, $responseReplacements, $arrayResponseTemplate);
    $arrayResponseParserInterface = ' implements ArrayResponseTransformerInterface';
    $arrayResponseParserInterfaceImport = 'use Yaspa\Interfaces\ArrayResponseTransformerInterface;' . PHP_EOL;
}

$classTemplate = <<<'CT'
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Yaspa\Exceptions\MissingExpectedAttributeException;
%arrayResponseParserInterfaceImport%use stdClass;

class %className%%arrayResponseParserInterface%
{
%dependencies%

%responseParser%

%arrayResponseParser%

    /**
     * @param stdClass $shopifyJson%className%
     * @return %className%Model
     */
    public function fromShopifyJson%className%(stdClass $shopifyJson%className%): %className%Model
    {
        $%classVariableName% = new %className%Model();

%propertyAssignments%
        return $%classVariableName%;
    }

    /**
     * @param %className%Model $%classVariableName%
     * @return array
     */
    public function toArray(%className%Model $%classVariableName%): array
    {
        $array = [];

%arrayAssignments%

        return $array;
    }
}
CT;

$classPlaceHolders = ['%className%', '%classVariableName%', '%responseParser%', '%arrayResponseParserInterface%', '%arrayResponseParserInterfaceImport%', '%arrayResponseParser%', '%dependencies%', '%propertyAssignments%', '%arrayAssignments%'];
$classReplacements = [$className, $classVariableName, $responseParser, $arrayResponseParserInterface, $arrayResponseParserInterfaceImport, $arrayResponseParser, implode(PHP_EOL, $dependencies), implode(PHP_EOL, $propertyAssignments), implode(PHP_EOL, $arrayAssignments)];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
