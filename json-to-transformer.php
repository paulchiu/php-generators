<?php

require_once __DIR__.'/lib/strings.php';
require_once __DIR__.'/lib/typed-templates.php';

$className = 'Customer';
$unwrap = true;
$classVariableName = lcfirst($className);
$snakeClassName = pascalToSnake($className);
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
    $arrayTemplate = getObjectArrayAssignmentTemplate();

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

$classPlaceHolders = ['%className%', '%classVariableName%', '%responseParser%', '%dependencies%', '%propertyAssignments%', '%arrayAssignments%'];
$classReplacements = [$className, $classVariableName, $responseParser, implode(PHP_EOL, $dependencies), implode(PHP_EOL, $propertyAssignments), implode(PHP_EOL, $arrayAssignments)];
$classContent = str_replace($classPlaceHolders, $classReplacements, $classTemplate);

echo $classContent;
