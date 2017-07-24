<?php

require_once __DIR__.'/lib/strings.php';

$className = 'Shop';
$unwrap = true;
$classVariableName = lcfirst($className);
$snakeClassName = pascalToSnake($className);
$json = <<<JS
{
  "ids": "3,5",
  "since_id": 7,
  "created_at_min": "2014-04-25T16:15:47-04:00",
  "created_at_max": "2014-04-25T16:15:47-04:00",
  "updated_at_min": "2014-04-25T16:15:47-04:00",
  "updated_at_max": "2014-04-25T16:15:47-04:00",
  "limit": 11,
  "page": 13,
  "customer_fields": {"foo": "bar"}
}
JS;

$jsonArray = json_decode($json, true);

$dependencyTemplate = <<<'DT'
    /** @var %capVariableName% $%camelName%Transformer */
    protected $%camelName%Transformer;

DT;

$propertyAssignmentTemplate = <<<'GST'
        if (property_exists($shopifyJson%className%, '%variableName%')) {
            $%classVariableName%->set%capVariableName%($shopifyJson%className%->%variableName%);
        }

GST;

$arrayAssignmentTemplate = <<<'AAT'
        if (property_exists($shopifyJson%className%, '%variableName%')) {
            $%camelName% = explode(',', $shopifyJson%className%->%variableName%);
            $%classVariableName%->set%capVariableName%($%camelName%);
        }

AAT;

$objectAssignTemplate = <<<'OAT'
        if (property_exists($shopifyJson%className%, '%variableName%')) {
            $%camelName% = $this->%camelName%Transformer
                ->fromShopifyJson%capVariableName%($shopifyJson%className%->%variableName%);
            $%classVariableName%->set%capVariableName%($%camelName%);
        }

OAT;

$dateAssignmentTemplate = <<<'DAT'
        if (property_exists($shopifyJson%className%, '%variableName%')) {
            $%camelName% = new DateTime($shopifyJson%className%->%variableName%);
            $%classVariableName%->set%capVariableName%($%camelName%);
        }

DAT;

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
foreach ($jsonArray as $variableName => $value) {
    // Compute replacement values
    list($hintType, $type, $getVerb, $quantNoun) = determineTypeVariables($value, $variableName);
    $capVariableName = snakeToPascal($quantNoun);
    $camelName = snakeToCamel($quantNoun);

    // Determine template
    switch ($type) {
        case 'string':
        case 'int':
        case 'float':
        case 'bool':
            $template = $propertyAssignmentTemplate;
            break;
        case 'array':
            $template = $arrayAssignmentTemplate;
            break;
        case 'DateTime':
            $template = $dateAssignmentTemplate;
            break;
        default:
            $template = $objectAssignTemplate;
    }

    // Do replacement
    $assignmentPlaceHolders = ['%className%', '%classVariableName%', '%variableName%', '%capVariableName%', '%camelName%'];
    $assignmentReplacements = [$className, $classVariableName, $variableName, $capVariableName, $camelName];
    $propertyAssignments[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $template);

    // Do object templates
    if (is_array($value)) {
        $dependencies[] = str_replace($assignmentPlaceHolders, $assignmentReplacements, $dependencyTemplate);
    }
}

$responseTemplate = ($unwrap) ? $unwrapResponseTemplate : $responseTemplate;
$responsePlaceHolders = ['%className%', '%snakeClassName%'];
$responseReplacements = [$className, $snakeClassName];
$responseParser = str_replace($responsePlaceHolders, $responseReplacements, $responseTemplate);

$classTemplate = <<<'CT'
class %className%
{
%dependencies%

%responseParser%

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
}
CT;

$classPlaceHolders = ['%className%', '%classVariableName%', '%responseParser%', '%dependencies%', '%propertyAssignments%'];
$classReplacements = [$className, $classVariableName, $responseParser, implode(PHP_EOL, $dependencies), implode(PHP_EOL, $propertyAssignments)];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
