<?php
    namespace DevLfg\Model;

    use \Devlfg\DB\Sql;
    use \Devlfg\Model;
    use \Devlfg\Mailer\Mailer;

    class User extends Model{

        const SESSION = "User";
        const IV = "Ecommerce_PHP_7_";
        const SECRET = "Ecommerce_PHP_7_";

        public static function login($login,$password){

            $sql = new Sql();

            $results = $sql->select("SELECT * FROM tb_users WHERE 
            deslogin = :LOGIN",array(":LOGIN" => $login));

            if(count($results) === 0){
                throw new \Exception("Usuário inexistente ou senha 
                inválida");
            }

            $data = $results[0];

            if(password_verify($password, $data["despassword"])){

                $user = new User();

                $user->setData($data);

                $_SESSION[User::SESSION] = $user->getValues();

                return $user;
            }
            else{
                throw new \Exception("Usuário inexistente ou senha 
                inválida");
            }
        }
        public static function verifyLogin($inadmin = true){

            if(!isset($_SESSION[User::SESSION]) || 
            !$_SESSION[User::SESSION] || 
            !(int)$_SESSION[User::SESSION]["iduser"] > 0 || 
            (bool) $_SESSION[User::SESSION]["inadmin"] !== $inadmin)
            {
                    header("Location: /admin/login");exit();
            }  
        }
        public static function logout(){

            $_SESSION[User::SESSION] = null;
        }
        public static function listAll(){

            $sql = new Sql();

            return $sql->select("SELECT * FROM tb_users U INNER JOIN 
            tb_persons P USING(idperson) ORDER BY P.desperson");
        }
        public function save(){
            $sql = new Sql();
            $result = $sql->select("CALL sp_users_save(:desperson,
            :deslogin,:despassword,:desemail,:nrphone, :inadmin)",
            array(
                ":desperson" => $this->getdesperson(),
                ":deslogin" => $this->getdeslogin(),
                ":despassword" => $this->getdespassword(),
                ":desemail" =>$this->getdesemail(),
                ":nrphone" => $this->getnrphone(),
                ":inadmin" => $this->getinadmin()
            ));

            $this->setData($result[0]);

        }
        public function get($idUser){

            $sql = new Sql();
            
            $results = $sql->select("SELECT * FROM tb_users U  
            INNER JOIN tb_persons P USING(idperson)
            WHERE U.iduser = :iduser",array(
               ":iduser" => $idUser 
            ));
            $this->setData($results[0]);
        }
        public function update(){
            $sql = new Sql();
            $result = $sql->select("CALL sp_usersupdate_save
            (:iduser,:desperson,
            :deslogin,:despassword,:desemail,:nrphone, :inadmin)",
            array(
                ":iduser" => $this->getiduser(),
                ":desperson" => $this->getdesperson(),
                ":deslogin" => $this->getdeslogin(),
                ":despassword" => $this->getdespassword(),
                ":desemail" =>$this->getdesemail(),
                ":nrphone" => $this->getnrphone(),
                ":inadmin" => $this->getinadmin()
            ));

            $this->setData($result[0]);
        }
        public function delete(){

            $sql = new Sql();
            $sql->query("CALL sp_users_delete(:iduser)",array(
                ":iduser" => $this->getiduser()
            ));
        }
        public static function getForgot($email){

            $sql = new Sql();

            $results = $sql->select("
                SELECT * FROM tb_persons P
                INNER JOIN tb_users U USING(idperson)
                WHERE P.desemail = :email
            ", array(
                ":email" => $email
            ));

            if(count($results) === 0)
                throw new \Exception("Não foi possível recuperar a senha");
            
            $data = $results[0];

            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser,
            :desip)",array(
                ":iduser" => $data["iduser"],
                ":desip"  => $_SERVER["REMOTE_ADDR"]
            ));

            if(count($results2) === 0)
                throw new \Exception("Não foi possível recuperar a senha");
            
            $dataRecovery = $results2[0];

            $code = base64_encode(openssl_encrypt(
                $dataRecovery["idrecovery"],
                "aes-128-cbc",
                User::SECRET,
                true,
                User::IV
            ));

            $link = "http://dev.ecommerce.com/admin/forgot/reset?code=$code";
            $mailer = new Mailer($data["desemail"],$data["desperson"],
            "Redefinir Senha da Ecommerce", "forgot", array(
                "name" => $data["desperson"],
                "link" => $link
            ));

            $mailer->send();

            return $data;

        }

        public static function validForgotDecrypt($code){
            $idrecovery = openssl_decrypt(
                base64_decode($code),
                "aes-128-cbc",
                User::SECRET,
                true,
                User::IV
            );
            
            $sql = new Sql();

            $results = $sql->select("
                SELECT * FROM tb_userspasswordsrecoveries UP
                INNER JOIN tb_users U USING(iduser)
                INNER JOIN tb_persons P USING(idperson)
                WHERE
                    UP.idrecovery = :idrecovery 
                    AND UP.dtrecovery IS NULL
                    AND DATE_ADD(UP.dtregister, INTERVAL 1 HOUR) >= NOW()
                ",array(
                    ":idrecovery" => $idrecovery
                )
            );

            if(count($results) === 0)
                throw new \Exception("Não foi possível recuperar a senha");
            
            return $results[0];
        }
        
        public static function setForgotUsed($idrecovery){

            $sql = new Sql();
            $sql->query("UPDATE tb_userspasswordsrecoveries 
            SET dtrecovery = NOW() WHERE idrecovery = :idrecovery",array(
                ":idrecovery" => $idrecovery
            ));
        }
        
        public function setPassword($password){
            $sql = new Sql();
            $sql->query("UPDATE tb_users
            SET despassword = :password 
            WHERE iduser = :iduser",array(
                ":password" => $password,
                ":iduser" => $this->getiduser()
            ));
        }
    }
?>