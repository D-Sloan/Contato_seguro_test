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
        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->insertOne($body, $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function updateOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->updateOne($args['id'], $body, $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function deleteOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->deleteOne($args['id'], $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }
}
