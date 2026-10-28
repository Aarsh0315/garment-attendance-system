<?php

namespace App\Http\Requests;

class UpdateOfficeLocationRequest extends OfficeLocationRequest
{
    public function rules(): array
    {
        return $this->detailRules();
    }
}
