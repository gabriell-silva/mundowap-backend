<?php

namespace App\Controller\Component;

use App\Model\Entity\Workday;
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
            ->toArray();

        if (!$workdays) {
            throw new \DomainException('Não existem dias úteis cadastrados.', 404);
        }

        return $workdays;
    }

    public function show(string $date): Workday
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

    public function create(array $data): Workday
    {
        $workday = $this->workDaysTable->newEmptyEntity();
        $workday = $this->workDaysTable->patchEntity($workday, $data);

        if ($this->workDaysTable->save($workday)) {
            return $workday;
        }

        throw new \DomainException('Erro ao cadastrar dia útil', 400);
    }

    public function closeDay(string $date): Workday
    {
        if (empty($date)) {
            throw new \DomainException('A data é obrigatória para fechar o dia de trabalho.', 400);
        }

        $visitsTable = TableRegistry::getTableLocator()->get('Visits');
        $pendingVisits = $visitsTable
            ->find()
            ->where([
                'date' => $date,
                'completed' => 0
            ])
            ->toArray();

        $visitsTable->updateAll(
            ['completed' => 1],
            ['date' => $date]
        );

        if (empty($pendingVisits)) {
            return $this->recalculateWorkday($date);
        }

        $workday = $this->recalculateWorkday($date);

        if ($workday) {
            $workday->completed = $workday->visits;
            $this->workDaysTable->save($workday);
        }

        return $workday;
    }

    private function reallocatePendingVisits(array $pendingVisits): void
    {
        if (empty($pendingVisits)) {
            return;
        }

        $visitsTable = TableRegistry::getTableLocator()->get('Visits');
        $currentDate = new \DateTime($pendingVisits[0]->date);
        $nextDay = clone $currentDate;
        $nextDay->modify('+1 day');
        $nextDayStr = $nextDay->format('Y-m-d');

        $maxDurationPerDay = 480;

        $nextDayDuration = $this->getDayTotalDuration($nextDayStr);
        $availableDuration = $maxDurationPerDay - $nextDayDuration;

        $remainingVisits = [];

        foreach ($pendingVisits as $visit) {
            $visit->completed = false;

            if ($availableDuration >= $visit->duration) {
                $visit->date = $nextDayStr;
                $visitsTable->save($visit);

                $availableDuration -= $visit->duration;
            } else {
                $remainingVisits[] = $visit;
            }
        }

        if (!empty($remainingVisits)) {
            $nextNextDay = clone $nextDay;
            $nextNextDay->modify('+1 day');

            foreach ($remainingVisits as $visit) {
                $visit->date = $nextNextDay->format('Y-m-d');
            }

            $this->reallocatePendingVisits($remainingVisits);
        }

        $this->recalculateWorkday($nextDayStr);
    }

    private function getDayTotalDuration(string $date): int
    {
        $visitsTable = TableRegistry::getTableLocator()->get('Visits');
        $visits = $visitsTable
            ->find()
            ->where(['date' => $date])
            ->toArray();

        $totalDuration = 0;
        foreach ($visits as $visit) {
            $totalDuration += $visit->duration;
        }

        return $totalDuration;
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
