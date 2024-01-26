<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Model\Product;
use Contatoseguro\TesteBackend\Service\CategoryService;
use Contatoseguro\TesteBackend\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductController
{
    private ProductService $service;
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->service = new ProductService();
        $this->categoryService = new CategoryService();
    }

    public function getAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if(!$request->getHeader('admin_user_id')){
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>'O cabeçalho "admin_user_id" é obrigatório.']));
            return $response->withStatus(400);
        }
        
        $adminUserId = $request->getHeader('admin_user_id')[0];


        $activeProduct = $request->getQueryParams()['active'] ?? null;
        $categoryId = $request->getQueryParams()['categoryId'] ?? null;

        $orderBy = $request->getQueryParams()['orderBy'] ?? null;
        $typeOrder = $request->getQueryParams()['typeOrder'] ?? null;
        
        $stm = $this->service->getAll($adminUserId, $activeProduct, $categoryId, $orderBy, $typeOrder);
        $response->getBody()->write(json_encode($stm->fetchAll()));
        return $response->withStatus(200);
    }

    public function lastUpdate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if(!$request->getHeader('admin_user_id')){
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>'O cabeçalho "admin_user_id" é obrigatório.']));
            return $response->withStatus(400);
        }

        $stm = $this->service->getOne($args['id']);
        $product = Product::hydrateByFetch($stm->fetch());

        
        $adminUserId = $request->getHeader('admin_user_id')[0];


        $productCategorys = $this->categoryService->getProductCategory($product->id)->fetchAll();
        
        $categorys = [];
        foreach( $productCategorys as $productCategory){
            $fetchedCategory = $this->categoryService->getOne($adminUserId, $productCategory->id)->fetch();

            $categorys[] = $fetchedCategory->title;
        }
        
        
        $product->setCategory($categorys);



        $stm = $this->service->getLastUpdate($args['id']);
        $productLogs = $stm->fetch();

        $completeLog = "";

        if($productLogs){
            $translateAction = [
                "create" => "Criação",
                "update" => "Atualização",
                "delete" => "Remoção"
            ];
    
            $logString = "(".$productLogs->user.", ".$translateAction[$productLogs->action].", ".date("d/m/Y H:i:s", strtotime($productLogs->timestamp)).")";
            
            if(empty($completeLog))
                $completeLog = $logString;
            else
                $completeLog .= ", ".$logString; 
        }else{
            $completeLog = "Sem atualização";
        }
        

        
        $product_array = [
            "id" => $product->id,
            "title" => $product->title,
            "category" => $product->category,
            "last_update" => $completeLog
        ];

        $response->getBody()->write(json_encode($product_array));
        return $response->withStatus(200);
    }

    public function getOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $stm = $this->service->getOne($args['id']);

        if(!$stm->fetch()){
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>"Produto não encontrado!"]));
            return $response->withStatus(404);
        }
        $product = Product::hydrateByFetch($stm->fetch());

        $adminUserId = $request->getHeader('admin_user_id')[0];
        $productCategorys = $this->categoryService->getProductCategory($product->id)->fetchAll();
        
        $categorys = [];
        foreach( $productCategorys as $productCategory){
            $fetchedCategory = $this->categoryService->getOne($adminUserId, $productCategory->id)->fetch();

            $categorys[] = $fetchedCategory->title;
        }
        
        
        $product->setCategory($categorys);

        $response->getBody()->write(json_encode($product));
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

        $response_data = $this->service->insertOne($body, $adminUserId);
        if (!is_string($response_data) && $response_data) {

            $response->getBody()->write(json_encode(["status"=>"success", "message"=>"Produto cadastrado com sucesso!"]));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(["status"=>"error", "message"=> is_string($response_data) ? $response_data : "Erro ao cadastrar produto"]));
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

        $response_data = $this->service->updateOne($args['id'], $body, $adminUserId);
        if (!is_string($response_data) && $response_data) {
            $response->getBody()->write(json_encode(["status"=>"success", "message"=>"Produto alterado com sucesso!"]));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(["status"=>"error", "message"=> is_string($response_data) ? $response_data : "Erro ao alterar produto"]));
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

        if($this->service->deleteOne($args['id'], $adminUserId)){
            $response->getBody()->write(json_encode(["status"=>"success", "message"=>"Produto removido com sucesso!"]));
            return $response->withStatus(200);
        }else{
            $response->getBody()->write(json_encode(["status"=>"error", "message"=>"Erro ao remover produto"]));
            return $response->withStatus(404);
        }
    }
}
