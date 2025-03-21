<?php
namespace App\Traits;

use Cake\Http\Response;

trait ApiResponse
{
    /**
     * Retorna uma resposta de sucesso formatada
     *
     * @param string $message Mensagem de sucesso
     * @param mixed $data Dados a serem retornados
     * @param int $code Código HTTP (padrão: 200)
     * @return \Cake\Http\Response
     */
    protected function success(string $message, $data = null, int $code = 200): Response
    {
        $response = $this->getResponse();

        // Verifica se $data tem estrutura de paginação
        if (
            is_object($data) &&
            property_exists($data, 'data') &&
            property_exists($data, 'links') &&
            property_exists($data, 'meta')
        ) {
            $responseData = [
                'success' => true,
                'data' => $data->data,
                'links' => $data->links,
                'meta' => $data->meta,
                'message' => $message
            ];
        } else {
            $responseData = [
                'success' => true,
                'data' => $data,
                'message' => $message
            ];
        }

        return $response
            ->withType('application/json')
            ->withStatus($code)
            ->withStringBody(json_encode($responseData));
    }

    /**
     * Retorna uma resposta de erro formatada
     *
     * @param string $message Mensagem de erro
     * @param int $code Código HTTP (padrão: 422)
     * @return \Cake\Http\Response
     */
    protected function error(string $message, int $code = 422): Response
    {
        $response = $this->getResponse();

        $responseData = [
            'success' => false,
            'message' => $message
        ];

        return $response
            ->withType('application/json')
            ->withStatus($code)
            ->withStringBody(json_encode($responseData));
    }
}
