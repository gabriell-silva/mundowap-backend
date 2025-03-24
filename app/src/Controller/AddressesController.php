<?php
declare(strict_types=1);

namespace App\Controller;

use App\Traits\ApiResponse;
use Cake\Http\Response;

class AddressesController extends AppController
{
    use ApiResponse;

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('AddressService');
    }

    public function store(): Response
    {
        try {
            if($this->request->is('post')) {
                $data = $this->request->getData();

                $address = $this->AddressService->create($data);

                return $this->success('Endereço cadastrado com sucesso!', $address);
            }
        } catch (\DomainException $domainException) {
            return $this->error($domainException->getMessage(), $domainException->getCode());
        } catch (\Exception $exception) {
            return $this->error('Erro interno do servidor!', 500);
        }

        return $this->error('Método não permitido', 405);
    }
}
