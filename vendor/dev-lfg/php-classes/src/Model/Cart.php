<?php
    namespace DevLfg\Model;

    use \Devlfg\DB\Sql;
    use \Devlfg\Model;
    use \Devlfg\Model\User;
    use \Devlfg\Model\Product;

    class Cart extends Model{
       
       const SESSION = "Cart";

       public static function getFromSession(){

            $cart = new Cart();

            if(isset($_SESSION[Cart::SESSION]) 
            && (int)$_SESSION[Cart::SESSION]['idcart'] > 0){
                $cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
            }else{
                $cart->getFromSessionID();

                if(!(int)$cart->getidcart() > 0){

                    $data = [
                        "dessessionid" => session_id(),    
                    ];

                    if(User::checkLogin(false)){
                        $user = User::getFromSession();

                        $data['iduser'] = $user->getiduser();
                    }

                    $cart->setData($data);

                    $cart->save();

                    $cart->setToSession();
                }
            }

            return $cart;
       }
       public function setToSession(){
            $_SESSION[Cart::SESSION] = $this->getValues();
       }
       public function getFromSessionID(){

            $sql = new Sql();

            $results = $sql->select("
                SELECT * FROM tb_carts WHERE dessessionid= :dessessionid
            ",[
                ":dessessionid" => session_id()
            ]);

            if(count($results) > 0)
                $this->setData($results[0]);
        }
       public function get(int $idcart){

            $sql = new Sql();

            $results = $sql->select("
                SELECT * FROM tb_carts WHERE idcart= :idcart
            ",[
                ":idcart" => $idcart
            ]);
            
            if(count($results) > 0)
                $this->setData($results[0]);
       }
       public function save(){

            $sql = new Sql();

            $results = $sql->select("
                CALL sp_carts_save(:idcart,:dessessionid,:iduser,
                :deszipcode,:vlfreight,:nrdays)
            ",[
                ':idcart' => $this->getidcart(),
                ':dessessionid' => $this->getdessessionid(),
                ':iduser' => $this->getiduser(),
                ':deszipcode' => $this->getdeszipcode(),
                ':vlfreight' => $this->getvlfreight(),
                ':nrdays' => $this->getnrdays()

            ]);

            $this->setData($results[0]);
       }

       public function addProduct(Product $product){

            $sql = new Sql();
            
            $sql->query("
                INSERT INTO tb_cartsproducts (idcart,idproduct)
                VALUES(:idcart, :idproduct)
            ",[
                ':idcart' => $this->getidcart(),
                ':idproduct' => $product->getidproduct()
            ]);
       }

       public function removeProduct(Product $product, $all = false){

            $sql = new Sql();

            if($all){
                $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW()
                 WHERE idcart = :idcart AND idproduct = :idproduct  AND
                 dtremoved IS NULL",[
                     ':idcart' => $this->getidcart(),
                     ':idproduct' => $product->getidproduct()
                 ]);
            }else{
                $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW()
                 WHERE idcart = :idcart AND idproduct = :idproduct AND
                 dtremoved IS NULL LIMIT 1",[
                     ':idcart' => $this->getidcart(),
                     ':idproduct' => $product->getidproduct()
                 ]);
            }
       }

       public function getProducts(){

            $sql = new Sql();

            return Product::checkList($sql->select("
              SELECT P.idproduct,P.desproduct,P.vlprice,P.vlprice,
              P.vlwidth,P.vlheight,P.vllength,P.vlweight,P.desurl,
              COUNT(*) as nrqtd, SUM(P.vlprice) AS vltotal
              FROM tb_cartsproducts CP
              INNER JOIN tb_products P
              ON CP.idproduct = P.idproduct
              WHERE CP.idcart = :idcart AND CP.dtremoved IS NULL
              GROUP BY P.idproduct,P.desproduct,P.vlprice,P.vlprice,
              P.vlwidth,P.vlheight,P.vllength,P.vlweight,P.desurl
              ORDER BY P.desproduct
            ",[
                ":idcart" => $this->getidcart()
            ]));
       }
       
    }
?>