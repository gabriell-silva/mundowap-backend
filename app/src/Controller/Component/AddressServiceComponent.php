<?php
declare(strict_types=1);

namespace App\Controller\Component;

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

    public function list()
    {
        return $this->getController()
            ->Addresses
            ->find()
            ->all();
    }

    public function show($id)
    {
        $address = $this->getController()
            ->Addresses
            ->find()
            ->where(['id' => $id])
            ->first();

        if(!$address) {
            throw new \DomainException('Endereço não encontrado', 404);
        }

        return $address;
    }

    public function create(array $data)
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

            return $result;
        } catch (\Exception $exception) {
            throw new \DomainException('Erro ao cadastrar endereço'.$exception, 500);
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
                $sublocality = empty($data['bairro']) ? $data['sublocality'] : $responseVC['bairro'];

                return [
                    'street' => $street,
                    'sublocality' => $sublocality,
                    'street_number' => $data['street_number'],
                    'complement' => $data['complement'] ?? null,
                    'city' => $responseVC['localidade'],
                    'state' => $responseVC['uf'],
                    'postal_code' => $data['cep']
                ];
            }
        }
    }
}
