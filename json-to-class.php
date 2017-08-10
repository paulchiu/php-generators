<?php

require_once __DIR__.'/lib/strings.php';

$className = 'Variant';
$json = <<<JS
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
JS;
$product_json = <<<PJS
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
PJS;

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
