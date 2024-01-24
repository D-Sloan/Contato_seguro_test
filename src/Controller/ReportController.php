<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\CompanyService;
use Contatoseguro\TesteBackend\Service\CategoryService;
use Contatoseguro\TesteBackend\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReportController
{
    private ProductService $productService;
    private CategoryService $categoryService;
    private CompanyService $companyService;
    
    public function __construct()
    {
        $this->productService = new ProductService();
        $this->categoryService = new CategoryService();
        $this->companyService = new CompanyService();
    }
    
    public function generate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];
        
        $data = [];
        $data[] = [
            'Id do produto',
            'Nome da Empresa',
            'Nome do Produto',
            'Valor do Produto',
            'Categorias do Produto',
            'Data de Criação',
            'Logs de Alterações'
        ];
        
        $stm = $this->productService->getAll($adminUserId);
        $products = $stm->fetchAll();

        foreach ($products as $i => $product) {
            $stm = $this->companyService->getNameById($product->company_id);
            $companyName = $stm->fetch()->name;

            $stm = $this->productService->getLog($product->id);
            $productLogs = $stm->fetchAll();



            $productCategorys = $this->categoryService->getProductCategory($product->id)->fetchAll();
        
            $categorys = "(";
            foreach( $productCategorys as $productCategory){
                $fetchedCategory = $this->categoryService->getOne($adminUserId, $productCategory->id)->fetch();

                if($categorys == "(")
                    $categorys .= $fetchedCategory->title;
                else
                    $categorys .= ", ".$fetchedCategory->title;
            }
            $categorys .= ")";


            $completeLog = "";

            foreach ($productLogs as $productLog){
                $translateAction = [
                    "create" => "Criação",
                    "update" => "Atualização",
                    "delete" => "Remoção"
                ];

                $logString = "(".$productLog->user.", ".$translateAction[$productLog->action].", ".date("d/m/Y H:i:s", strtotime($productLog->timestamp)).")";
                
                if(empty($completeLog))
                    $completeLog = $logString;
                else
                    $completeLog .= ", ".$logString; 
            }
            
            
            $data[$i+1][] = $product->id;
            $data[$i+1][] = $companyName;
            $data[$i+1][] = $product->title;
            $data[$i+1][] = $product->price;
            $data[$i+1][] = $categorys;
            $data[$i+1][] = $product->created_at;
            $data[$i+1][] = $completeLog;
        }
        
        $report = "<table style='font-size: 10px;'>";
        foreach ($data as $row) {
            $report .= "<tr>";
            foreach ($row as $column) {
                $report .= "<td>{$column}</td>";
            }
            $report .= "</tr>";
        }
        $report .= "</table>";
        
        $response->getBody()->write($report);
        return $response->withStatus(200)->withHeader('Content-Type', 'text/html');
    }
}
