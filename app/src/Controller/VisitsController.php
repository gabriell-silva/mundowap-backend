<?php
declare(strict_types=1);

namespace App\Controller;

use App\Traits\ApiResponse;
use Cake\Http\Response;

class VisitsController extends AppController
{
    use ApiResponse;

    // Carrega o component, para separação da regra de negócio do controller
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('VisitService');
    }

    public function visitByDate(string $date): Response
    {
        try {
            $visit = $this->VisitService->list($date);

            return $this->success('Visitas listadas com sucesso!', $visit);
        } catch (\DomainException $domainException) {
            return $this->error($domainException->getMessage(), $domainException->getCode());
        } catch (\Exception $exception) {
            return $this->error('Erro ao listar visitas', 500);
        }

        return $this->error('Método não permitido', 405);
    }

    public function add(): Response
    {
        try {
            if($this->request->is('post')) {
                $data = $this->request->getData();

                $visit = $this->VisitService->create($data);

                return $this->success('Visita registrada com sucesso!', $visit);
            }
        } catch (\DomainException $domainException) {
            return $this->error($domainException->getMessage(), $domainException->getCode());
        } catch (\Exception $exception) {
            return $this->error('Erro ao registrar visita', 500);
        }

        return $this->error('Método não permitido', 405);
    }

    public function edit(int $id): Response
    {
        try {
            if($this->request->is('put')) {
                $data = $this->request->getData();

                $visit = $this->VisitService->update($id, $data);

                return $this->success('Visita atualizada com sucesso!', $visit);
            }
        } catch (\DomainException $domainException) {
            return $this->error($domainException->getMessage(), $domainException->getCode());
        } catch (\Exception $exception) {
            return $this->error('Erro ao atualizar visita', 500);
        }

        return $this->error('Método não permitido', 405);
    }
}
