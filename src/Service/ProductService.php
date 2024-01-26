<?php

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Config\DB;

class ProductService
{
    private \PDO $pdo;
    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    public function getAll($adminUserId, $activeProduct = null, $categoryId = null, $orderBy = null, $typeOrder = null)
    {
        $query = "
        SELECT p.*, GROUP_CONCAT(c.title) as categories
        FROM product p
        INNER JOIN product_category pc ON pc.product_id = p.id
        INNER JOIN category c ON c.id = pc.cat_id
            WHERE p.company_id = {$adminUserId}
            ". (!is_null($activeProduct) ? ("AND p.active = ".$activeProduct) : "")."
            ". (!is_null($categoryId) ? ("AND c.id = ".$categoryId) : "")."
            GROUP BY p.id
            ". (!is_null($orderBy) ? ("ORDER BY ".$orderBy." ".$typeOrder) : "");

        $stm = $this->pdo->prepare($query);

        $stm->execute();

        return $stm;
    }

    public function getOne($id)
    {
        $stm = $this->pdo->prepare("
            SELECT *
            FROM product
            WHERE id = {$id}
        ");
        $stm->execute();

        return $stm;
    }

    public function insertOne($body, $adminUserId)
    {

        $this->pdo->beginTransaction();

        try {

            $stm = $this->pdo->prepare("
                INSERT INTO product (
                    company_id,
                    title,
                    price,
                    active
                ) VALUES (
                    {$body['company_id']},
                    '{$body['title']}',
                    {$body['price']},
                    {$body['active']}
                )
            ");
            if (!$stm->execute()){
                $this->pdo->rollBack();
                return false;
            }

            $productId = $this->pdo->lastInsertId();

            if(is_array($body['category_id'])){
                foreach($body['category_id'] as $category_id){
                    $stm = $this->pdo->prepare("
                        INSERT INTO product_category (
                            product_id,
                            cat_id
                        ) VALUES (
                            {$productId},
                            {$category_id}
                        );
                    ");
                    if (!$stm->execute()){
                        $this->pdo->rollBack();
                        return false;
                    }
                }
            }else{
                $stm = $this->pdo->prepare("
                    INSERT INTO product_category (
                        product_id,
                        cat_id
                    ) VALUES (
                        {$productId},
                        {$body['category_id']}
                    );
                ");
                if (!$stm->execute()){
                    $this->pdo->rollBack();
                    return false;
                }
            }

            $stm = $this->pdo->prepare("
                INSERT INTO product_log (
                    product_id,
                    admin_user_id,
                    `action`
                ) VALUES (
                    {$productId},
                    {$adminUserId},
                    'create'
                )
            ");

            if (!$stm->execute()){
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        }catch(\Exception $exception){
            $this->pdo->rollBack();
            return false;
        }
    }

    public function updateOne($id, $body, $adminUserId)
    {

        $this->pdo->beginTransaction();

        try {

            $stm = $this->pdo->prepare("
                UPDATE product
                SET company_id = {$body['company_id']},
                    title = '{$body['title']}',
                    price = {$body['price']},
                    active = {$body['active']}
                WHERE id = {$id}
            ");
            if (!$stm->execute()){
                $this->pdo->rollBack();
                return false;
            }


            //Irei limpar a tabela de product_category relacionada ao produto, para então inserir novas relações enviadas
            $stm = $this->pdo->prepare("
                DELETE FROM product_category WHERE product_id = {$id}
            ");
            if (!$stm->execute()){
                $this->pdo->rollBack();
                return false;
            }

            //Agora irei inserir novamente baseado nos dados enviados em category_id, pois agora aceitará arrays para que o produto possa ter mais de 1 categoria.
            if(is_array($body['category_id'])){
                foreach($body['category_id'] as $category_id){
                    $stm = $this->pdo->prepare("
                        INSERT INTO product_category (
                            product_id,
                            cat_id
                        ) VALUES (
                            {$id},
                            {$category_id}
                        );
                    ");
                    if (!$stm->execute()){
                        $this->pdo->rollBack();
                        return false;
                    }
                }

            }else{
                $stm = $this->pdo->prepare("
                    INSERT INTO product_category (
                        product_id,
                        cat_id
                    ) VALUES (
                        {$id},
                        {$body['category_id']}
                    );
                ");
                if (!$stm->execute()){
                    $this->pdo->rollBack();
                    return false;
                }
            }

            $stm = $this->pdo->prepare("
                INSERT INTO product_log (
                    product_id,
                    admin_user_id,
                    `action`
                ) VALUES (
                    {$id},
                    {$adminUserId},
                    'update'
                )
            ");

            if (!$stm->execute()){
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        }catch(\Exception $exception){
            $this->pdo->rollBack();
            return false;
        }
    }

    public function deleteOne($id, $adminUserId)
    {

        $this->pdo->beginTransaction();

        try {
            $stm = $this->pdo->prepare("
                DELETE FROM product_category WHERE product_id = {$id}
            ");
            if (!$stm->execute()){
                $this->pdo->rollBack();
                return false;
            }
            
            $stm = $this->pdo->prepare("DELETE FROM product WHERE id = {$id}");
            if (!$stm->execute()){
                $this->pdo->rollBack();
                return false;
            }

            $stm = $this->pdo->prepare("
                INSERT INTO product_log (
                    product_id,
                    admin_user_id,
                    `action`
                ) VALUES (
                    {$id},
                    {$adminUserId},
                    'delete'
                )
            ");

            if (!$stm->execute()){
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        }catch(\Exception $exception){
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getLog($id)
    {
        $stm = $this->pdo->prepare("
            SELECT pl.*, au.name as user
            FROM product_log pl
            INNER JOIN admin_user au ON au.id = pl.admin_user_id
            WHERE product_id = {$id}
        ");
        $stm->execute();

        return $stm;
    }

    public function getLastUpdate($id)
    {
        $stm = $this->pdo->prepare("
            SELECT pl.*, au.name as user
            FROM product_log pl
            INNER JOIN admin_user au ON au.id = pl.admin_user_id
            WHERE product_id = {$id}
            AND action = 'update'
            ORDER BY timestamp DESC
        ");
        $stm->execute();

        return $stm;
    }
}
