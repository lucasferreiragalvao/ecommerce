<?php
    namespace DevLfg\Model;

    use \Devlfg\DB\Sql;
    use \Devlfg\Model;
    use \Devlfg\Model\Product;

    class Category extends Model{

        public static function listAll(){

            $sql = new Sql();

            return $sql->select("SELECT * FROM tb_categories
             ORDER BY descategory");
        }
        public function save(){
            $sql = new Sql();
            $result = $sql->select("CALL sp_categories_save(:idcategory,
            :descategory)",
            array(
                ":idcategory" => $this->getidcategory(),
                ":descategory" => $this->getdescategory()
            ));

            $this->setData($result[0]);

            Category::updateFile();
        }
        public function get($idcategory){
            $sql = new Sql();

            $results = $sql->select("SELECT * FROM tb_categories 
            WHERE idcategory = :idcategory",[
                ":idcategory" => $idcategory
            ]);

            $this->setData($results[0]);
        }
        public function delete(){

            $sql = new Sql();
            $sql->query("DELETE FROM tb_categories 
             WHERE idcategory = :idcategory",[
                 ":idcategory" => $this->getidcategory()
             ]);
             
             Category::updateFile();
        }
        public static function updateFile(){
            $categories = Category::listAll();
            
            $html = [];

            foreach($categories as $row){
                array_push($html,'<li><a href="/categories/'.$row['idcategory'].'">'
                .$row['descategory']
                .'</a></li>');
            }

            file_put_contents($_SERVER['DOCUMENT_ROOT']. DIRECTORY_SEPARATOR.
            "views" . DIRECTORY_SEPARATOR . "categories-menu.html", 
            implode('',$html));
        }

        public function getProducts($related = true){

            $sql = new Sql();
            if($related === true){

                    return $sql->select("
                        SELECT * FROM tb_products WHERE idproduct IN(
                            SELECT P.idproduct
                            FROM tb_products P
                            INNER JOIN tb_productscategories PC
                            ON P.idproduct = PC.idproduct
                            WHERE PC.idcategory = :idcategory   
                        )
                    ",[
                        ":idcategory" => $this->getidcategory()
                    ]);

            }else{
                    return $sql->select("
                    SELECT * FROM tb_products WHERE idproduct NOT IN(
                        SELECT P.idproduct
                        FROM tb_products P
                        INNER JOIN tb_productscategories PC
                        ON P.idproduct = PC.idproduct
                        WHERE PC.idcategory = :idcategory      
                    )
                ",[
                    ":idcategory" => $this->getidcategory()
                ]);
            }
        }

        public function getProductsPage($page = 1, $itensPerPage = 8){

            $sql = new Sql();

            $start = ($page-1) * $itensPerPage;

            $results = $sql->select("
                SELECT SQL_CALC_FOUND_ROWS *
                FROM tb_products P
                INNER JOIN tb_productscategories PC on P.idproduct = PC.idproduct
                INNER JOIN tb_categories C ON C.idcategory = PC.idcategory
                WHERE C.idcategory = :idcategory
                LIMIT $start ,$itensPerPage;
            ",[
                ":idcategory" => $this->getidcategory()
            ]);
            
            $resultTotal = $sql->select("SELECT FOUND_ROWS() as nrtotal;");

            return [
                'data' => Product::checkList($results),
                'total' => (int)$resultTotal[0]["nrtotal"],
                'pages' => ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
            ];

        }
        public function addProduct( Product $product){

            $sql = new Sql();

            $sql->query("INSERT INTO tb_productscategories
                        (idcategory, idproduct) VALUES (:idcategory,
                        :idproduct)", [
                            ":idcategory" => $this->getidcategory(),
                            ":idproduct" => $product->getidproduct()
                        ]);
        }

        public function removeProduct( Product $product){

            $sql = new Sql();

            $sql->query("DELETE FROM tb_productscategories
                        WHERE idcategory = :idcategory
                        AND idproduct = :idproduct", [
                            ":idcategory" => $this->getidcategory(),
                            ":idproduct" => $product->getidproduct()
                        ]);
        }
       
    }
?>