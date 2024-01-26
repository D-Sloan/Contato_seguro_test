<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\CategoryService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CategoryController
{
    private CategoryService $service;

    public function __construct()
    {
        $this->service = new CategoryService();
    }

    public function getAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if(!$request->getHeader('admin_user_id')){
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>'O cabeçalho "admin_user_id" é obrigatório.']));
            return $response->withStatus(400);
        }

        $adminUserId = $request->getHeader('admin_user_id')[0];
        
        $stm = $this->service->getAll($adminUserId);
        $response->getBody()->write(json_encode($stm->fetchAll()));
        return $response->withStatus(200);
    }

    public function getOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if(!$request->getHeader('admin_user_id')){
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>'O cabeçalho "admin_user_id" é obrigatório.']));
            return $response->withStatus(400);
        }

        $adminUserId = $request->getHeader('admin_user_id')[0];
        $stm = $this->service->getOne($adminUserId, $args['id']);


        if(!$stm->fetch()){
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>"Categoria não encontrada!"]));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode($stm->fetchAll()));
        return $response->withStatus(200);
    }

    public function insertOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if(!$request->getHeader('admin_user_id')){
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>'O cabeçalho "admin_user_id" é obrigatório.']));
            return $response->withStatus(400);
        }

        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->insertOne($body, $adminUserId)) {
            $response->getBody()->write(json_encode(["status"=>"success", "message"=>"Categoria cadastrada com sucesso!"]));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>"Erro ao cadastrar categoria"]));
            return $response->withStatus(404);
        }
    }

    public function updateOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if(!$request->getHeader('admin_user_id')){
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>'O cabeçalho "admin_user_id" é obrigatório.']));
            return $response->withStatus(400);
        }

        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->updateOne($args['id'], $body, $adminUserId)) {
            $response->getBody()->write(json_encode(["status"=>"success", "message"=>"Categoria alterada com sucesso!"]));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>"Erro ao alterar categoria"]));
            return $response->withStatus(404);
        }
    }

    public function deleteOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if(!$request->getHeader('admin_user_id')){
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>'O cabeçalho "admin_user_id" é obrigatório.']));
            return $response->withStatus(400);
        }

        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->deleteOne($args['id'], $adminUserId)) {
            $response->getBody()->write(json_encode(["status"=>"success", "message"=>"Categoria removida com sucesso!"]));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>"Erro ao remover categoria"]));
            return $response->withStatus(404);
        }
    }
}
