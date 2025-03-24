<?php

namespace App\Controller\Component;

use Cake\Controller\Component;

class VisitServiceComponent extends Component
{
    protected $_components = ['Addresses'];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        // Garante que o component AddressService seja carregado
        if (!$this->getController()->components()->has('AddressService')) {
            $this->getController()->loadComponent('AddressService');
            $this->AddressService = $this->getController()->AddressService;
        }

        if (!$this->getController()->components()->has('WorkdaysService')) {
            $this->getController()->loadComponent('WorkdaysService');
            $this->WorkdaysService = $this->getController()->WorkdaysService;
        }
    }

    public function list(string $date)
    {
        try {
            $visit = $this->getController()
                ->Visits
                ->find()
                ->where(['date' => $date]);

            if(!$visit) {
                throw new \DomainException('Não existe visita para data informada.', 404);
            };

            return $visit;
        } catch (\Exception $exception) {
            throw new \DomainException('Erro ao buscar visita', 400);
        }
    }

    public function create(array $data)
    {
        try {
            $visits = $this->getController()->Visits;

            // Transação, garantindo salvar visita juntamente do endereço
            $connection = $visits->getConnection();
            return $connection->transactional(function () use ($visits, $data) {
                $addressId = null;

                if (!empty($data['address'])) {
                    $addressData = [
                        'postal_code' => $data['address']['postal_code'],
                        'street' => $data['address']['street'],
                        'street_number' => $data['address']['street_number'],
                        'sublocality' => $data['address']['sublocality'],
                        'complement' => $data['address']['complement'],
                        'foreign_table' => 'visits',
                        'foreign_id' => 0
                    ];

                    $address = $this->AddressService->create($addressData);

                    if (!$address) {
                        throw new \DomainException('Erro ao cadastrar endereço', 422);
                    }

                    $addressId = $address->id;
                }

                $visitData = $data;
                if (isset($visitData['address'])) {
                    unset($visitData['address']);
                }

                if($addressId) {
                    $visitData['address_id'] = $addressId;
                }

                // Calcular duração da visita
                $duration = $this->calcDuration($data['forms'], $data['products']);

                if ($duration > 480) {
                    throw new \DomainException('Limite de horas excedido!', 400);
                }

                $visitData['duration'] = $duration;
                $visitData['completed'] = $data['completed'] ?? 0;
                $entity = $visits->newEntity($visitData);
                $entity = $visits->patchEntity($entity, $visitData);

                $result = $visits->save($entity);

                if(!$result) {
                    $errors = $entity->getErrors();
                    $errorMsg = 'Erro ao cadastrar visita';

                    foreach ($errors as $field => $fieldErrors) {
                        foreach ($fieldErrors as $rule => $message) {
                            $errorMsg .= "$field: $message";
                        }
                    }

                    throw new \DomainException($errorMsg, 422);
                }

                $this->WorkdaysService->recalculateWorkday($data['date']);

                if ($addressId && isset($addressData['foreign_id']) && $addressData['foreign_id'] === 0) {
                    $address = $this->AddressService->addressesTable->get($addressId);
                    $address->foreign_id = $entity->id;
                    $this->AddressService->addressesTable->save($address);
                }

                return $entity;
            });
        } catch (\Exception $exception) {
            throw new \DomainException('Erro ao cadastrar visita'.$exception, 500);
        }
    }

    public function update(int $id, array $data)
    {
        try {
            $visitsTable = $this->getController()->Visits;
            $addressesTable = $this->AddressService->addressesTable;

            // Carrega o relacionamento
            $visitEntity = $visitsTable->get($id, [
                'contain' => ['Addresses', 'Workdays']
            ]);

            $originalDate = date('Y-m-d', strtotime($visitEntity->date));

            $connection = $visitsTable->getConnection();

            return $connection->transactional(function () use ($visitsTable, $addressesTable, $data, $visitEntity, $originalDate) {
                // Verifica se há um novo endereço

                if (isset($data['address'])) {
                    if ($visitEntity['address']->id) {
                        try {
                            $addressEntity = $addressesTable->get($visitEntity['address']->id);
                            $addressesTable->delete($addressEntity);
                        } catch (\Exception $e) {
                            throw new \DomainException('Erro ao deletar endereço', 422);
                        }
                    }

                    $addressData = [
                        'postal_code' => $data['address']['postal_code'] ?? '',
                        'street' => $data['address']['street'] ?? '',
                        'street_number' => $data['address']['street_number'] ?? '',
                        'sublocality' => $data['address']['sublocality'] ?? '',
                        'complement' => $data['address']['complement'] ?? '',
                        'foreign_table' => 'visits',
                        'foreign_id' => $visitEntity->id
                    ];

                    try {
                        $newAddress = $this->AddressService->create($addressData);
                        if (!$newAddress) {
                            throw new \DomainException('Erro ao criar novo endereço', 422);
                        }
                        $data['address_id'] = $newAddress->id;
                    } catch (\Exception $e) {
                        throw new \DomainException('Erro ao criar novo endereço: ' . $e->getMessage(), 422);
                    }
                }

                if (isset($data['address'])) {
                    unset($data['address']);
                }

                $visit = $visitsTable->patchEntity($visitEntity, $data);

                // Recalcular duração da visita, caso tenha alterado
                if (isset($data['forms']) || isset($data['products'])) {
                    $forms = $data['forms'] ?? $visitEntity->forms;
                    $products = $data['products'] ?? $visitEntity->products;
                    $visit->duration = $this->calcDuration($forms, $products);
                }

                if ($visitsTable->save($visit)) {
                    if (isset($data['date'])) {
                        $newDate = date('Y-m-d', strtotime($data['date']));

                        $this->WorkdaysService->recalculateWorkday($originalDate);
                        $this->WorkdaysService->recalculateWorkday($data['date']);
                    } else {
                        $this->WorkdaysService->recalculateWorkday($visit->date);
                    }

                    return $visitsTable->get($visit->id, [
                        'contain' => ['Addresses', 'Workdays']
                    ]);
                }

                throw new \DomainException('Erro ao atualizar visita', 422);
            });
        } catch (\Exception $exception) {
            throw new \DomainException('Erro ao atualizar visita' . $exception, 500);
        }
    }

    public function calcDuration(int $form, int $products): int
    {
        $duration = 0;

        if($form || $products) {
            $duration = ($form * 15) + ($products * 5);
        }

        return $duration;
    }
}
