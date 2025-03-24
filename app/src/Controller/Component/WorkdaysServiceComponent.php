<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

class WorkdaysServiceComponent extends Component
{
    protected $workDaysTable;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->workDaysTable = TableRegistry::getTableLocator()->get('Workdays');
    }

    public function list(): array
    {
        $workdays = $this->workDaysTable
            ->find()
            ->all();

        if (!$workdays) {
            throw new \DomainException('Não existem dias úteis cadastrados.', 404);
        }

        return $workdays->toArray();
    }

    public function show(string $date): object
    {
        $workday = $this->workDaysTable
            ->find()
            ->where(['date' => $date])
            ->first();

        if (!$workday) {
            throw new \DomainException('Dia útil não encontrado!', 404);
        }

        return $workday;
    }

    public function create(array $data): object
    {
        $workday = $this->workDaysTable->newEmptyEntity();
        $workday = $this->workDaysTable->patchEntity($workday, $data);

        if ($this->workDaysTable->save($workday)) {
            return $workday;
        }

        throw new \DomainException('Erro ao cadastrar dia útil', 400);
    }

    public function closeDay(array $date): string
    {
        return 'Dia útil fechado com sucesso!';
    }

    public function recalculateWorkday(string $date): object
    {
        $visits = TableRegistry::getTableLocator()
            ->get('Visits')
            ->find()
            ->where(['date' => $date])
            ->toArray();

        $totalVisits = count($visits);
        $completedVisits = 0;
        $totalDuration = 0;

        foreach ($visits as $visit) {
            if ($visit->completed) {
                $completedVisits++;
            }

            $totalDuration += $visit->duration;
        }

        if($totalDuration > 480) {
            throw new \DomainException('Limite de horas atingido!', 400);
        }

        try {
            $workday = $this->show($date);
            $workday->visits = $totalVisits;
            $workday->completed = $completedVisits;
            $workday->duration = $totalDuration;
        } catch (\DomainException $e) {
            if ($e->getMessage() === 'Dia útil não encontrado!') {
                $workdayData = [
                    'date' => $date,
                    'visits' => $totalVisits,
                    'completed' => $completedVisits,
                    'duration' => $totalDuration
                ];

                $workday = $this->create($workdayData);
                return $workday;
            } else {
                throw $e;
            }
        }

        if ($this->workDaysTable->save($workday)) {
            return $workday;
        }

        throw new \DomainException('Erro ao recalcular dia útil', 400);
    }

}
