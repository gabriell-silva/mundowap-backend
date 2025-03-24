<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Model\Entity\Address;
use Cake\Controller\Component;
use Cake\Http\Client;
use Cake\ORM\TableRegistry;

class AddressServiceComponent extends Component
{
    protected $addressesTable;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addressesTable = TableRegistry::getTableLocator()->get('Addresses');
    }

    public function create(array $data): Address
    {
        $address = $this->searchAddress($data);

        $address['foreign_table'] = $data['foreign_table'] ?? 'default';
        $address['foreign_id'] = $data['foreign_id'] ?? 1;

        $entity = $this->addressesTable->newEmptyEntity();
        $entity = $this->addressesTable->patchEntity($entity, $address);

        $result = $this->addressesTable->save($entity);

        if (!$result) {
            $errors = $entity->getErrors();

            $errorMsg = 'Erro ao salvar endereço';

            foreach ($errors as $field => $fieldErrors) {
                foreach ($fieldErrors as $rule => $message) {
                    $errorMsg .= "$field: $message; ";
                }
            }

            throw new \DomainException($errorMsg, 422);
        }

        return $result;
    }

    private function searchAddress(array $data): array
    {
        $postal_code = $data['postal_code'];

        $http = new Client();

        $responseRV = $http->get("https://republicavirtual.com.br/web_cep.php?cep=$postal_code&formato=json")->getJson();

        if (isset($responseRV['resultado']) && $responseRV['resultado'] == 1) {
            $street = empty($responseRV['tipo_logradouro']) && empty($responseRV['logradouro']) ? $data['street'] : $responseRV['tipo_logradouro'] . ' ' . $responseRV['logradouro'];
            $sublocality = empty($responseRV['bairro']) ? $data['sublocality'] : $responseRV['bairro'];

            return [
                'street' => $street,
                'sublocality' => $sublocality,
                'street_number' => $data['street_number'],
                'complement' => $data['complement'] ?? '',
                'city' => $responseRV['cidade'],
                'state' => $responseRV['uf'],
                'postal_code' => $data['postal_code']
            ];
        }

        $responseVC = $http->get("https://viacep.com.br/ws/{$postal_code}/json")->getJson();

        if (isset($responseVC['erro']) && !!$responseVC['erro']) {
            throw new \DomainException('CEP inválido', 422);
        }

        $street = empty($responseVC['logradouro']) ? $data['street'] : $responseVC['logradouro'];
        $sublocality = empty($responseVC['bairro']) ? $data['sublocality'] : $responseVC['bairro'];

        return [
            'street' => $street,
            'sublocality' => $sublocality,
            'street_number' => $data['street_number'],
            'complement' => $data['complement'] ?? '',
            'city' => $responseVC['localidade'],
            'state' => $responseVC['uf'],
            'postal_code' => $data['postal_code']
        ];
    }
}
