<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Datasource\ResultSetInterface;
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

    public function list(): ResultSetInterface
    {
        $addresses = $this->getController()
            ->Addresses
            ->find()
            ->all();

        foreach ($addresses as $address) {
            $address->postal_code = $this->formatPostalCode($address->postal_code);
        }

        return $addresses;
    }

    public function show($id): object
    {
        $address = $this->getController()
            ->Addresses
            ->find()
            ->where(['id' => $id])
            ->first();

        if(!$address) {
            throw new \DomainException('Endereço não encontrado', 404);
        }

        $address->postal_code = $this->formatPostalCode($address->postal_code);

        return $address;
    }

    public function create(array $data): object
    {
        try {
            $postal_code = $data['postal_code'];

            $address = $this->searchAddress($postal_code, $data);

            // Adicionar os campos necessários
            $address['foreign_table'] = $data['foreign_table'] ?? 'default';
            $address['foreign_id'] = $data['foreign_id'] ?? 1;

            $entity = $this->addressesTable->newEmptyEntity();
            $entity = $this->addressesTable->patchEntity($entity, $address);

            $result = $this->addressesTable->save($entity);

            if (!$result) {
                $errors = $entity->getErrors();
                $errorMsg = 'Erro ao salvar endereço: ';

                foreach ($errors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $rule => $message) {
                        $errorMsg .= "$field: $message; ";
                    }
                }

                throw new \DomainException($errorMsg, 422);
            }

            $result->postal_code = $this->formatPostalCode($result->postal_code);

            return $result;
        } catch (\Exception $exception) {
            throw new \DomainException('Erro ao cadastrar endereço', 500);
        }
    }

    public function searchAddress(string $postal_code, array $data): array
    {
        $http = new Client();

        $responseRV = $http->get("https://republicavirtual.com.br/web_cep.php?cep=$postal_code&formato=json")->getJson();

        if(isset($responseRV['resultado']) && $responseRV['resultado'] == 1) {
            $street = empty($responseRV['tipo_logradouro']) && empty($responseRV['logradouro']) ? $data['street'] : $responseRV['tipo_logradouro'] . ' ' . $responseRV['logradouro'];
            $sublocality = empty($responseRV['bairro']) ? $data['sublocality'] : $responseRV['bairro'];

            return [
                'street' => $street,
                'sublocality' => $sublocality,
                'street_number' => $data['street_number'],
                'complement' => $data['complement'] ?? null,
                'city' => $responseRV['cidade'],
                'state' => $responseRV['uf'],
                'postal_code' => $data['postal_code']
            ];
        } else {
            $http = new Client();

            $responseVC = $http->get("https://viacep.com.br/ws/{$postal_code}/json")->getJson();

            if(isset($responseVC['erro']) && !!$responseVC['erro']) {
                throw new \DomainException('CEP inválido', 422);
            } else {
                $street = empty($responseVC['logradouro']) ? $data['street'] : $responseVC['logradouro'];
                $sublocality = empty($responseVC['bairro']) ? $data['sublocality'] : $responseVC['bairro'];

                return [
                    'street' => $street,
                    'sublocality' => $sublocality,
                    'street_number' => $data['street_number'],
                    'complement' => $data['complement'] ?? null,
                    'city' => $responseVC['localidade'],
                    'state' => $responseVC['uf'],
                    'postal_code' => $data['postal_code']
                ];
            }
        }
    }

    private function formatPostalCode(string $postalCode): string
    {
        $postalCode = preg_replace('/[^0-9]/', '', $postalCode);

        return substr($postalCode, 0, 5) . '-' . substr($postalCode, 5, 3);
    }
}
