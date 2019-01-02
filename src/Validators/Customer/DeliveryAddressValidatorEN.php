<?php

namespace IO\Validators\Customer;

use Plenty\Validation\Validator;
use IO\Services\TemplateConfigService;

class DeliveryAddressValidatorEN extends Validator
{
    private $requiredFields;
    private $shownFields;

    public static $addressData;
    
    public function defineAttributes()
    {
        /**
         * @var TemplateConfigService $templateConfigService
         */
        $templateConfigService = pluginApp(TemplateConfigService::class);
        $requiredFieldsString  = $templateConfigService->get('delivery_address.en.require');
        $this->requiredFields  = explode(', ', $requiredFieldsString);
        $shownFieldsString  = $templateConfigService->get('delivery_address.en.show');
        $this->shownFields  = explode(', ', $shownFieldsString);
        foreach ($this->requiredFields as $key => $value)
        {
            $this->requiredFields[$key] = str_replace('delivery_address.', '', $value);
        }

        foreach ($this->shownFields as $key => $value)
        {
            $this->shownFields[$key] = str_replace('delivery_address.', '', $value);
        }
    
        $this->addString('address1', true);
        $this->addString('postalCode', true);
        $this->addString('town',       true);

        $hasContactPerson = $this->isShown('salutation') && $this->isShown('name1') && empty(self::$addressData['gender']) || 
            !$this->isShown('salutation') && $this->isShown('name1');

        $this->addString('name1', $hasContactPerson);
        $this->addString('name2', !$hasContactPerson);
        $this->addString('name3', !$hasContactPerson);
        $this->addString('contactPerson', $hasContactPerson);
        
        if(count($this->requiredFields))
        {
            $this->addString('name4',     $this->isRequired('name4'));
            $this->addString('address2',  $this->isRequired('address2'));
            $this->addString('address3',  $this->isRequired('address3'));
            $this->addString('address4',  $this->isRequired('address4'));
            $this->addString('title',     $this->isRequired('title'));
            $this->addString('telephone', $this->isRequired('telephone'));
        }
    }
    
    private function isRequired($fieldName)
    {
        return in_array($fieldName, $this->shownFields) && in_array($fieldName, $this->requiredFields);
    }

    private function isShown($fieldName)
    {
        return in_array($fieldName, $this->shownFields);
    }
}
