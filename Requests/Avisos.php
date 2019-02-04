<?php
require_once("../Includes/Headers.php");

class Avisos{

    public function CreateAviso($data){
        Database::GetInstance()->BeginTransaction();
        $result = Database::GetInstance()->InsertGeneric("avisos",$data);
        $alunos = self::GetAlunosTurma($data);
        foreach($alunos as $aluno){
            $mailData  = array(
                "MailOperation" => "SendNotification",
                "mensagem" => $data['mensagem'],
                "Email" => $aluno['Email']
            );
            $result = Email::GetInstance()->SendEmail($mailData);
            if($result['code'] != 0){
                Database::GetInstance()->RollBack();
                exit("ocorreu um erro ao enviar a sua mensagem");
            }
        }
        Database::GetInstance()->Commit();
        return $result;
    }

    public function GetAvisosTurma($data){
        $Sql = "SELECT mensagem, data
        FROM avisos
        WHERE turma= ?";
        return Database::GetInstance()->SelectQuery($Sql, array( $data['turma'] ) );
    }

    private function GetAlunosTurma($data){
        $Sql = "SELECT Email 
        FROM usuario
        WHERE Turma = ? ";
        return Database::GetInstance()->SelectQuery($Sql, array( $data['turma'] ) );
    }

}

function CreateMockData(){
    return array(
        "turma" => "A",
        "mensagem" => "liberado novo quesitonário"
    );
}

function CreateMockData2(){
    return array(
        "turma" => "A",
    );
}

$aviso = new Avisos();

if(isset($_GET['operation'])){
    echo $aviso->{$_GET['operation']}();
}
else if(isset($_POST['operation'])){

    $data = json_decode($_POST['data'],true);
    echo json_encode($aviso->{$_POST['operation']}($data));
}
else{
    exit("operação invalida");
}

?>