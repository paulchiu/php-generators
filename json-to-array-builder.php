<?php

require_once __DIR__.'/lib/strings.php';

$className = 'ProductFields';
$arrayName = 'fields';
$capArrayName = ucfirst($arrayName);
$json = <<<JS
{
    "id": 632910392,
    "title": "IPod Nano - 8GB",
    "body_html": "<p>It's the small iPod with one very big idea: Video. Now the world's most popular music player, available in 4GB and 8GB models, lets you enjoy TV shows, movies, video podcasts, and more. The larger, brighter display means amazing picture quality. In six eye-catching colors, iPod nano is stunning all around. And with models starting at just $149, little speaks volumes.</p>",
    "vendor": "Apple",
    "product_type": "Cult Products",
    "created_at": "2017-07-24T19:09:43-04:00",
    "handle": "ipod-nano",
    "updated_at": "2017-07-24T19:09:43-04:00",
    "published_at": "2007-12-31T19:00:00-05:00",
    "metafields_global_title_tag": "foo",
    "metafields_global_description_tag": "foo",
    "template_suffix": null,
    "published_scope": "web",
    "tags": "Emotive, Flash Memory, MP3, Music",
    "variants": [
      {
        "id": 808950810,
        "product_id": 632910392,
        "title": "Pink",
        "price": "199.00",
        "sku": "IPOD2008PINK",
        "position": 1,
        "grams": 567,
        "inventory_policy": "continue",
        "compare_at_price": null,
        "fulfillment_service": "manual",
        "inventory_management": "shopify",
        "option1": "Pink",
        "option2": null,
        "option3": null,
        "created_at": "2017-07-24T19:09:43-04:00",
        "updated_at": "2017-07-24T19:09:43-04:00",
        "taxable": true,
        "barcode": "1234_pink",
        "image_id": 562641783,
        "inventory_quantity": 10,
        "weight": 1.25,
        "weight_unit": "lb",
        "old_inventory_quantity": 10,
        "requires_shipping": true
      }
    ],
    "options": [
      {
        "id": 594680422,
        "product_id": 632910392,
        "name": "Color",
        "position": 1,
        "values": [
          "Pink",
          "Red",
          "Green",
          "Black"
        ]
      }
    ],
    "images": [
      {
        "id": 850703190,
        "product_id": 632910392,
        "position": 1,
        "created_at": "2017-07-24T19:09:43-04:00",
        "updated_at": "2017-07-24T19:09:43-04:00",
        "width": 123,
        "height": 456,
        "src": "https://cdn.shopify.com/s/files/1/0006/9093/3842/products/ipod-nano.png?v=1500937783",
        "variant_ids": [
        ]
      },
      {
        "id": 562641783,
        "product_id": 632910392,
        "position": 2,
        "created_at": "2017-07-24T19:09:43-04:00",
        "updated_at": "2017-07-24T19:09:43-04:00",
        "width": 123,
        "height": 456,
        "src": "https://cdn.shopify.com/s/files/1/0006/9093/3842/products/ipod-nano-2.png?v=1500937783",
        "variant_ids": [
          808950810
        ]
      }
    ],
    "image": {
      "id": 850703190,
      "product_id": 632910392,
      "position": 1,
      "created_at": "2017-07-24T19:09:43-04:00",
      "updated_at": "2017-07-24T19:09:43-04:00",
      "width": 123,
      "height": 456,
      "src": "https://cdn.shopify.com/s/files/1/0006/9093/3842/products/ipod-nano.png?v=1500937783",
      "variant_ids": [3]
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
