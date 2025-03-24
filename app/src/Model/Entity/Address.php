<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Address Entity
 *
 * @property int $id
 * @property string $foreign_table
 * @property int $foreign_id
 * @property string $postal_code
 * @property string $state
 * @property string $city
 * @property string $sublocality
 * @property string $street
 * @property string $street_number
 * @property string $complement
 * @property \Cake\I18n\FrozenTime $created_at
 * @property \Cake\I18n\FrozenTime $updated_at
 */
class Address extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */

    protected function _getPostalCode(?string $value): ?string
    {
        if (empty($value)) {
            return $value;
        }

        $value = preg_replace('/[^0-9]/', '', $value);

        if (strlen($value) === 8) {
            return substr($value, 0, 5) . '-' . substr($value, 5, 3);
        }

        return $value;
    }


    protected $_accessible = [
        'foreign_table' => true,
        'foreign_id' => true,
        'postal_code' => true,
        'state' => true,
        'city' => true,
        'sublocality' => true,
        'street' => true,
        'street_number' => true,
        'complement' => true,
        'created_at' => true,
        'updated_at' => true,
    ];
}
