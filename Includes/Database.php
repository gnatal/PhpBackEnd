<?php
class Database{
    private static $connection = "";
    private static $instance = "";

    public static function GetInstance(){
        if(self::$instance == null){
            self::$instance = new Database();
            self::$instance->ConnectToDatabase();
        }
        return self::$instance;
    }

    private function ConnectToDatabase(){
        $dataConnect = self::GetConfigData();
        self::$connection = new PDO($dataConnect['dsn'], $dataConnect['user'], $dataConnect['password']);
        self::$connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    }

    private function GetConfigData(){
        $config = parse_ini_file("../Configs/database.ini");
        $dataConnect = array(
            'dsn' => $config['dsn'],
            'user' => $config['user'],
            'password' => $config['password']
        );
        return $dataConnect;
    }


    public function SelectQuery($Sql,$params = []){
        self::$connection->exec("set names utf8");
        $Statement = self::$connection->prepare($Sql);
        for($i =0; $i < count($params); $i++)
            $Statement -> bindValue($i+1, $params[$i]);
        $Statement->execute();
        $e = self::GetError();
        $Result = array();
        if ($e[0]=='0'){
            $i = 0;
		    while ($linha = $Statement->fetch(PDO::FETCH_NAMED)){
                $Result[$i] = $linha; 
                $i++;
            }
        }
        else {   
            error_log("Erro na Query: " . json_encode($e). ".
            Query realizada: " . $sql, 0); 
            return array(
                'code' => 1,
                'message' => $e
            );
        }
        return $Result;    
    }

    public function Query($sql){
        self::$connection->exec("set names utf8");
        $stmt = self::$connection->query($sql);
        $e = self::GetError();
        $Result = array();
        if ($e[0]=='0'){
            $i = 0;
		    while ($linha = $stmt->fetch(PDO::FETCH_NAMED)){
                $Result[$i] = $linha; 
                $i++;
            }
        }
        else {   
            error_log("Erro na Query: " . json_encode($e). ".
            Query realizada: " . $sql, 0); 
            return array(
                'code' => 1,
                'message' => $e
            );
        }
        return $Result;
    }

    public function UpdateQuery($Sql, $params = []){
        self::$connection->exec("set names utf8");
        $stmt = self::$connection->prepare($Sql);
        for($i =0; $i < count($params); $i++)
            $stmt -> bindValue($i+1, $params[$i]);
        $stmt->execute();
        if ($stmt->rowCount()){            
            return array(
                'code' => 0,
                'message' => 'Update realizado com sucesso'
            );
        }
        else {
            error_log("Erro no statement do Update : " . json_encode($stmt->errorInfo()). ".
            Update realizada: " . $Sql, 0 ); 
            return array(
                'code' => 1,
                'message' => $stmt->errorInfo()
            );
        }

    }


    public function DeleteQuery($Sql){
        $Statement = self::$connection->prepare($Sql);
        $Result = $Statement->execute();
        if ($Statement->rowCount() > 0){            
            return array(
                'code' => 0,
                'message' => "Registro deletado com sucessos"
            );
        }
        else {
            error_log("Erro no statement do delete : " . json_encode($Statement->errorInfo()). ".
            Delete realizada: " . $Sql, 0 ); 
            return array(
                'code' => 1,
                'message' => $Statement->errorInfo()
            );
        }
    }

    public function InsertGeneric($TableName,$data){

        self::$connection->exec("set names utf8");
        foreach($data as $key => $value){
            $DummyArray[] = "?";
            $values[] = $value;
            $fields[] = $key;
        }

        $DummyArray = implode(",",$DummyArray);
        $fields = implode(",",$fields);

        $sql = "INSERT INTO $TableName ($fields) 
        VALUES ($DummyArray)";

        $stmt = self::$connection->prepare($sql);
        $result = $stmt->execute($values);
        if ($result == true){            
            return array(
                'code' => 0,
                'message' => 'Novo registro criado com sucesso'
            );
        }
        else {
            error_log("Erro no statement do InsertGeneric: " . json_encode($stmt->errorInfo()). ".
            Insert realizada: " . $sql, 0 ); 
            return array(
                'code' => 1,
                'message' => $stmt->errorInfo()
            );
        }
    }

    public function BeginTransaction(){
        self::$connection->beginTransaction();
    }

    public function Commit(){
        self::$connection->commit();
    }

    public function RollBack(){
        self::$connection->rollBack();
    }


    public function GetError(){
        return self::$connection->errorInfo();
    }

    public function LastID(){
        return self::$connection->lastInsertID();
    }
}
