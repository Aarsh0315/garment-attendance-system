<?php

namespace App\Http\Requests;

class StoreOfficeLocationRequest extends OfficeLocationRequest
{
    public function rules(): array
    {
        return $this->detailRules();
    }
}
