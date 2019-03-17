<?php
    namespace DevLfg\Model;

    use \Devlfg\DB\Sql;
    use \Devlfg\Model;
    use \Devlfg\Model\Cart;

    class Order extends Model{

        const SESSION_ERROR = "OrderError";
        const SUCCESS = "OrderSuccess";

        public function save(){

            $sql = new Sql();

            $results = $sql->select("
                CALL sp_orders_save(
                    :idorder,
                    :idcart,
                    :iduser,
                    :idstatus,
                    :idaddress,
                    :vltotal
                )
            ",[
                ':idorder' => $this->getidorder(),
                ':idcart' => $this->getidcart(),
                ':iduser' => $this->getiduser(),
                ':idstatus' => $this->getidstatus(),
                ':idaddress' => $this->getidaddress(),
                ':vltotal' => $this->getvltotal()
                

            ]);

            if(count($results) > 0){
                $this->setData($results[0]);
            }

        }

        public function get($idorder){

            $sql = new Sql();

            $results = $sql->select("
                SELECT *
                FROM tb_orders O
                INNER JOIN tb_ordersstatus OS USING(idstatus)
                INNER JOIN tb_carts C USING(idcart)
                INNER JOIN tb_users U 
                ON U.iduser = O.iduser
                INNER JOIN tb_addresses A USING(idaddress)
                INNER JOIN tb_persons P
                ON P.idperson = U.idperson
                WHERE O.idorder = :idorder
            ",[
                ':idorder' => $idorder
            ]);

            if(count($results) > 0){
                $this->setData($results[0]);
            }
        }

        public static function listAll(){

            $sql = new Sql();

            return $sql->select("
                SELECT *
                FROM tb_orders O
                INNER JOIN tb_ordersstatus OS USING(idstatus)
                INNER JOIN tb_carts C USING(idcart)
                INNER JOIN tb_users U 
                ON U.iduser = O.iduser
                INNER JOIN tb_addresses A USING(idaddress)
                INNER JOIN tb_persons P
                ON P.idperson = U.idperson
                ORDER BY O.dtregister DESC
            ");


        }

        public function delete(){

            $sql = new Sql();

            $sql->query("DELETE FROM tb_orders WHERE idorder = :idorder",[
                ":idorder" => $this->getidorder()
            ]);
        }

        public function getCart():Cart
        {

            $cart = new Cart();

            $cart->get((int)$this->getidcart());

            return $cart;
        }

        public static function setError($msg){
            $_SESSION[User::SESSION_ERROR] = $msg;
        }
 
        public static function getError(){
            $msg = (isset($_SESSION[User::SESSION_ERROR]))
            ? $_SESSION[Order::SESSION_ERROR] : "";
 
            Order::clearError();
 
            return $msg;
        }
        public static function clearError(){
 
            $_SESSION[Order::SESSION_ERROR] = NULL;
        }

        public static function setSuccess($msg){
            $_SESSION[Order::SUCCESS] = $msg;
        }
 
        public static function getSuccess(){
            $msg = (isset($_SESSION[Order::SUCCESS]))
            ? $_SESSION[Order::SUCCESS] : "";
 
            Order::clearSuccess();
 
            return $msg;
        }
        public static function clearSuccess(){
 
            $_SESSION[Order::SUCCESS] = NULL;
        }
        public static function getPage($page = 1, $itensPerPage = 10){

            $sql = new Sql();

            $start = ($page-1) * $itensPerPage;

            $results = $sql->select("
                SELECT SQL_CALC_FOUND_ROWS *
                FROM tb_orders O
                INNER JOIN tb_ordersstatus OS USING(idstatus)
                INNER JOIN tb_carts C USING(idcart)
                INNER JOIN tb_users U 
                ON U.iduser = O.iduser
                INNER JOIN tb_addresses A USING(idaddress)
                INNER JOIN tb_persons P
                ON P.idperson = U.idperson
                ORDER BY O.dtregister DESC
                LIMIT $start ,$itensPerPage;
            ");
            
            $resultTotal = $sql->select("SELECT FOUND_ROWS() as nrtotal;");

            return [
                'data' => $results,
                'total' => (int)$resultTotal[0]["nrtotal"],
                'pages' => ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
            ];

        }

        public static function getPageSearch($search , $page = 1, $itensPerPage = 10){

            $sql = new Sql();

            $start = ($page-1) * $itensPerPage;

            $results = $sql->select("
                SELECT SQL_CALC_FOUND_ROWS *
                FROM tb_orders O
                INNER JOIN tb_ordersstatus OS USING(idstatus)
                INNER JOIN tb_carts C USING(idcart)
                INNER JOIN tb_users U 
                ON U.iduser = O.iduser
                INNER JOIN tb_addresses A USING(idaddress)
                INNER JOIN tb_persons P
                ON P.idperson = U.idperson
                WHERE O.idorder = :id OR
                P.desperson LIKE :search
                ORDER BY O.dtregister DESC
                LIMIT $start ,$itensPerPage;
            ",[
                ':id' => $search,
                ':search' => '%'.$search.'%'
            ]);
            
            $resultTotal = $sql->select("SELECT FOUND_ROWS() as nrtotal;");

            return [
                'data' => $results,
                'total' => (int)$resultTotal[0]["nrtotal"],
                'pages' => ceil($resultTotal[0]["nrtotal"] / $itensPerPage)
            ];

        }
    }
?>