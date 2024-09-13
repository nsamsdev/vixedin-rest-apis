<?php

namespace Vixedin\System\Modules;

use Vixedin\System\Model;

/**
 * @class Access
 */
class CustomerSettings
{
    /**
     * Undocumented variable
     *
     * @var array
     */
    private array $userSettingsKeys = [
        'first_name' => null,
        'second_name' => null,
        'username' => null,
        'address_line1' => null,
        'address_line2' => null,
        'postcode' => null,
        'contact_number' => null,
        'email_validation' => null,
        'user_level' => null,
    ];

    /**
     * Undocumented function
     *
     * @param integer $userId
     * @param [type] $model
     */
    public function __construct(public int $customerId, public Model $model = new Model())
    {
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public static function getSettingKeys(): array
    {
        return [
            'first_name' => null,
            'second_name' => null,
            'username' => null,
            'address_line1' => null,
            'address_line2' => null,
            'postcode' => null,
            'contact_number' => null,
        ];
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function getSettings(): array
    {
        $settings = $this->model->getCustomerSettings($this->customerId);

        foreach ($settings as $setting) {
            if (array_key_exists($setting['key_name'], $this->userSettingsKeys)) {
                $this->userSettingsKeys[$setting['key_name']] = $setting['key_value'];
            }
        }

        return $this->userSettingsKeys;
    }


    public static function getUniqueSettings(): array
    {
        return [
            'username'
        ];
    }

}
