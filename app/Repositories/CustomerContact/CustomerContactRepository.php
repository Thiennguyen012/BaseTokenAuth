<?php

namespace App\Repositories\CustomerContact;

use App\Models\CustomerContacts\CustomerContact;
use App\Repositories\Base\BaseRepository;

class CustomerContactRepository extends BaseRepository implements CustomerContactInterface
{
    public function model(): string
    {
        return CustomerContact::class;
    }
}
