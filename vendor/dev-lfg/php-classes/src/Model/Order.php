<?php
    namespace DevLfg\Model;

    use \Devlfg\DB\Sql;
    use \Devlfg\Model;

    class Order extends Model{

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
    }
?>