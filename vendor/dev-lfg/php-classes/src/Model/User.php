<?php
    namespace DevLfg\Model;

    use \Devlfg\DB\Sql;
    use \Devlfg\Model;
    use \Devlfg\Mailer\Mailer;

    class User extends Model{

        const SESSION = "User";
        const PASSWORD = "Password";
        const IV = "Ecommerce_PHP_7_";
        const SECRET = "Ecommerce_PHP_7_";
        const SESSION_ERROR = "UserError";
        const SESSION_ERROR_REGISTER = "UserErrorRegister";

        public static function getFromSession(){

            $user = new User();
            if(isset($_SESSION[User::SESSION]) &&
            (int)$_SESSION[User::SESSION]['iduser'] > 0){
                $user->setData($_SESSION[User::SESSION]);
            }
            return $user;
        }
        public static function checkLogin($inadmin = true){
            //Não está Logado
            if(
                !isset($_SESSION[User::SESSION]) || 
                !$_SESSION[User::SESSION] || 
                !(int)$_SESSION[User::SESSION]["iduser"] > 0
            )
            {
                return false;
            }
            else{
                if($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'])
                    return true;
                else if($inadmin === false)
                    return true;
                else
                    return false;
            }
        }
        public static function login($login,$password){

            $sql = new Sql();

            $results = $sql->select("SELECT * FROM tb_users U
            INNER JOIN tb_persons P
            ON U.idperson = P.idperson
            WHERE U.deslogin = :LOGIN",array(":LOGIN" => $login));

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

            if(!User::checkLogin($inadmin))
            {       
                    if($inadmin){
                        header("Location: /admin/login");
                    }
                    else{
                        header("Location: /login");
                    }
                    exit;
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
                ":despassword" => User::getPasswordHash($this->getdespassword()),
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
                ":despassword" => User::getPasswordHash($this->getdespassword()),
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

        public static function setError($msg){
            $_SESSION[User::SESSION_ERROR] = $msg;
        }
 
        public static function getError(){
            $msg = (isset($_SESSION[User::SESSION_ERROR]))
            ? $_SESSION[User::SESSION_ERROR] : "";
 
            User::clearError();
 
            return $msg;
        }
 
        public static function clearError(){
 
             $_SESSION[User::SESSION_ERROR] = NULL;
        }

        public static function setErrorRegister($msg){
            $_SESSION[User::SESSION_ERROR_REGISTER] = $msg;
        }
 
        public static function getErrorRegister(){
            $msg = (isset($_SESSION[User::SESSION_ERROR_REGISTER]))
            ? $_SESSION[User::SESSION_ERROR_REGISTER] : "";
 
            User::clearErrorRegister();
 
            return $msg;
        }
 
        public static function clearErrorRegister(){
 
             $_SESSION[User::SESSION_ERROR_REGISTER] = NULL;
        }

        public static function getPasswordHash($password){
            return password_hash($password, PASSWORD_DEFAULT,[
                'cost' =>12
            ]);
        }

        public function checkLoginExist($login){

            $sql = new Sql();

            $results = $sql->select("SELECT * FROM tb_users
            WHERE deslogin = :deslogin",[
                ':deslogin' => $login
            ]);

            return (count($results) > 0);
        }
    }
?>