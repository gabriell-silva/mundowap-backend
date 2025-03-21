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

    public function list()
    {
        $workdays = $this->workDaysTable
            ->find()
            ->all();

        if (!$workdays) {
            throw new \DomainException('Não existem dias úteis cadastrados.', 404);
        }

        return $workdays;
    }

    public function show(string $date)
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

    public function create(array $data)
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

}
