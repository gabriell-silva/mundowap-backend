<?php
declare(strict_types=1);

namespace App\Controller;

use App\Traits\ApiResponse;
use Cake\Http\Response;

class WorkdaysController extends AppController
{
    use ApiResponse;

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('WorkdaysService');
    }

    public function index(): Response
    {
        try {
            $workdays = $this->WorkdaysService->list();

            return $this->success('Dias úteis listados com sucesso!', $workdays);
        } catch (\DomainException $domainException) {
            return $this->error($domainException->getMessage(), $domainException->getCode());
        } catch (\Exception $exception) {
            return $this->error('Erro interno do servidor!', 500);
        }
    }

    public function add(): Response
    {
        try {
            if ($this->request->is('post')) {
                $data = $this->request->getData();

                $workday = $this->WorkdaysService->create($data);

                return $this->success('Dia de útil cadastrado com sucesso!', $workday);
            }
        } catch (\DomainException $domainException) {
            return $this->error($domainException->getMessage(), $domainException->getCode());
        } catch (\Exception $exception) {
            return $this->error('Erro interno do servidor!', 500);
        }

        return $this->error('Método não permitido', 405);
    }

    public function close(): Response
    {
        try {
            if ($this->request->is('post')) {
                $data = $this->request->getData();

                $workday = $this->WorkdaysService->closeDay($data);

                return $this->success('Dia de útil fechado com sucesso!', $workday);
            }

        } catch (\DomainException $domainException) {
            return $this->error($domainException->getMessage(), $domainException->getCode());
        } catch (\Exception $exception) {
            return $this->error('Erro interno do servidor!', 500);
        }

        return $this->error('Método não permitido', 405);
    }
}
