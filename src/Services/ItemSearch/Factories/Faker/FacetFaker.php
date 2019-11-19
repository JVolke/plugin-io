<?php

namespace IO\Services\ItemSearch\Factories\Faker;

class FacetFaker extends AbstractFaker
{
    public $facetTypes = [
        "availability" => "IO::Faker.facetNameAvailability",
        "category"     => "IO::Faker.facetNameCategory",
        "dynamic"      => "IO::Faker.facetNameDynamic",
        "price"        => "IO::Faker.facetNamePrice"
    ];

    public function fill($data)
    {
        $default = [];

        foreach ($this->facetTypes as $type => $name)
        {
            $default[] = $this->makeFacet($type, $name);
        }

        $this->merge($data, $default);
        return $data;
    }

    private function makeFacet($type, $name)
    {
        return [
            'id' => $type,
            'name' => $this->trans($name),
            'position' => 0,
            'values' => $this->makeValues(),
            "type" => $type
        ];
    }

    private function makeValues()
    {
        $result = [];

        for ($i = 1; $i <= $this->number(3, 10); $i++)
        {
            $result[] = [
                'id' => $i.'',
                'name' => $this->trans("IO::Faker.facetValueName"),
                'count' => $this->number(1, 10),
            ];
        }

        return $result;
    }
}
