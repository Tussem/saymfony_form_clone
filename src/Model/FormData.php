<?php
// src/Model/FormData.php
namespace App\Model;

class FormData
{
    private array $dynamicFields = [];

    public function setDynamicField(string $fieldName, $value): void
    {
        $this->dynamicFields[$fieldName] = $value;
    }

    public function getDynamicField(string $fieldName)
    {
        return $this->dynamicFields[$fieldName] ?? null;
    }

    public function getDynamicFields(): array
    {
        return $this->dynamicFields;
    }
}
