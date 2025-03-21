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

                    if (!$this->AddressService) {
                        throw new \DomainException('Erro ao cadastrar endereço', 422);
                    }

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
                $entity = $visits->newEntity($visitData);
                $entity = $visits->patchEntity($entity, $visitData);

                try {
                    $workDay = $this->WorkdaysService->show($data['date']);

                    $newCompleted = $workDay->completed;

                    if ($entity->completed) {
                        $newCompleted += 1;
                    }

                    $newTotalDuration = $workDay->duration + $duration;

                    if ($newTotalDuration > 480) {
                        throw new \DomainException('Limite de horas excedido!', 400);
                    }

                    $workDay->completed = $newCompleted;
                    $workDay->duration = $newTotalDuration;

                    $this->WorkdaysService->workDaysTable->save($workDay);
                } catch (\DomainException $e) {
                    if ($e->getMessage() === 'Dia útil não encontrado!') {
                        if ($duration > 480) {
                            throw new \DomainException('Limite de horas atingido', 400);
                        }

                        $workdayData = [
                            'date' => $data['date'],
                            'visits' => 1,
                            'completed' => $entity->completed ? 1 : 0,
                            'duration' => $duration
                        ];

                        $workDay = $this->WorkdaysService->create($workdayData);
                    } else {
                        throw $e;
                    }
                }

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

    public function update(array $data, $visitEntity)
    {
        try {
            $visit = $this->Visits>patchEntity($visitEntity, $data);

            return $this->Visits->save($visit);
        } catch (\Exception $exception) {
            throw new \DomainException('Erro ao atualizar visita', 500);
        }
    }

    public function calcDuration(int $form, int $products): int
    {
        $duration = 0;

        if($form && $products) {
            $duration = ($form * 15) + ($products * 5);
        }

        return $duration;
    }
}
