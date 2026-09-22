<?php

namespace App\Rules;

use App\Models\MsPart;
use Illuminate\Contracts\Validation\Rule;

class PartPricingRule implements Rule
{
    protected $row = 0;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $component = explode('.', $attribute);
        $this->row = intval($component[0]) + 2;

        $part = MsPart::where('PartID', $value)->first();
        if($part){
            return $part->Pricing == 'BasedOnPrice';
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Row ' . $this->row . ' : PartID is not Based on Price!';
    }
}
