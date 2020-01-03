<?php

namespace IO\Services\ItemSearch\Extensions;

class VariationPropertyExtension implements ItemSearchExtension
{
    /**
     * @inheritdoc
     */
    public function getSearch($parentSearchBuilder)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function transformResult($baseResult, $extensionResult)
    {
        if(count($baseResult['documents']))
        {
            foreach($baseResult['documents'] as $key => $extensionDocument)
            {
                foreach($extensionDocument['data']['variationProperties'] as $propertyKey => $property)
                {
                    //set the id of the property on the first level for frontend mapping
                    $test = $property['property']['id'];
                    $baseResult['documents'][$key]['data']['variationProperties'][$test]['propertyId'] = $property['property']['id'];
                }
            }
        }

        return $baseResult;
    }
}
