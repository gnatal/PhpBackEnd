<?php

require_once("../Includes/Headers.php");

class Login{

    public function CreateUser($user){

        Database::GetInstance()->BeginTransaction();
        if(Email::GetInstance()->CheckMailExistence($user['Email']) == 0)
            return array(
                "code" => 4,
                "message" => "Email inexistente"
            );
            
        $result = Database::GetInstance()->InsertGeneric("usuarios",$user);
        
        if($result['code'] != 0)
            return $result;

        $result = Database::GetInstance()->InsertGeneric("userhash",self::CreateVerifyUserData($user));

        if($result['code'] != 0){
            Database::GetInstance()->RollBack();
            return $result;
        }

        $result = Email::GetInstance()->SendEmail(self::GenerateConfirmationMailData($user));

        if($result['code'] != 0){
            Database::GetInstance()->RollBack();
            return $result;
        }

        Database::GetInstance()->Commit();

        return $result;
    }

    private function CreateVerifyUserData($user){
        $verificaUserData = array(
            "Hash" => self::GenerateUserToken($user),
            'UsuarioID' => Database::GetInstance()->LastID()
        );

        return $verificaUserData;
    }

    public function GenerateUserToken($user){
        return sha1($user['Email']).$user['Senha'];
    }

    private function GenerateConfirmationMailData($user){
        $mailData["MailOperation"] = "CreateUser";
        $mailData["link"] = "http://localhost/prospectit_server/Requests/Login.php?operation=GetIdFromHash&hash=".self::GenerateUserToken($user);
        $mailData["Email"] = $user["Email"];
        return $mailData;
    }

    private function VerificaUsuario($data)
    {
        $sql = "UPDATE usuarios
		SET Verificado = 1	
		WHERE UsuarioID = '" .$data['UsuarioID']. "'";
        return Database::GetInstance()->UpdateQuery($sql);
    }

    public function GetIdFromHash($hash){
        $Sql = "SELECT UsuarioID 
        FROM userhash
        WHERE Hash = ?";
        $idUserArray = Database::GetInstance()->SelectQuery($Sql, array( $hash ) );
        $result = self::VerificaUsuario($idUserArray[0]);
        if($result['code'] == 0 )
            $result = self::ConfirmationMailMessage();
        return $result;
    }

    public function SignIn($data){
        $Sql = " SELECT UsuarioID, Email, Verificado
        FROM usuarios
        WHERE Email= ? AND Senha = ?";
        $result = Database::GetInstance()->SelectQuery($Sql, array( $data['Email'], $data['Senha'] ) );
        if(!isset($result[0]))
            return array(
                "code" => 5,
                "message" => "usuario ou senha incorretos"
            );
        return array(
            "code" => 0,
            "message" => "Login realizado com sucesso",
            "data" => $result[0]
        );
    }

    private function ConfirmationMailMessage(){
        return "
        <h1 >Login confirmado com sucesso</h1>
        <p> Você já pode user nossa platforma normalmente</p>
        <a href='https://www.youtube.com/watch?v=mhvegS-jSr8' target='_blank'>Clique aqui e volte para app</a>
        ";
    }

    public function GenerateNewPassword($data){
        $password = self::GenerateRandomString();
        $hashedPass = sha1($password);
        $Sql = "UPDATE usuario
                SET Senha = ?
                WHERE Email = ?
        ";       

        $result = Database::GetInstance()->UpdateQuery($Sql,array($hashedPass, $data['Email']));
        if($result['code'] != 0){
            $result['message'] = "Senha não alterada tente novamente mais tarde";
            return $result;
        }

        $mailData['MailOperation'] = "PasswordRecovery";
        $mailData['Email'] = $data['Email'];
        $mailData['password'] = $password;

        $result = Email::GetInstance()->SendEmail($mailData);
        return $result;
    }

    private function GenerateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }   


}

$Login = new Login();

if(isset($_GET['operation'])){
    if(isset($_GET['hash']))
        echo $Login->{$_GET['operation']}($_GET['hash']);
    else
        echo json_encode($Login->{$_GET['operation']}());

}
else if(isset($_POST['operation'])){

    $data = json_decode($_POST['data'],true);

    echo json_encode($Login->{$_POST['operation']}($data));
}
else{
    exit("operação invalida");
}




?>
