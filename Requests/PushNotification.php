<?php
require_once "../Includes/Headers.php";

class PushNotification{

    private static $AuthKey = "<YourAuthKey>";

    public function SendMessageToTopic($data){
        $url = 'https://fcm.googleapis.com/fcm/send';

        $msg =  array
        (
            'body'  => $data['message'],
            'title'     => "Portal IAE",
            'vibrate'   => 1,
            'sound'     => 1,
        );
        
    

        $fields = array(
            'to' => '/topics/'.$data['topic'],
            'notification'  => $msg
        );
        
        $fields = json_encode ( $fields );
    
        $headers = array (
                'Authorization: key='.self::$AuthKey,
                'Content-Type: application/json'
        );
    
        $ch = curl_init ();

        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
    
        $result = curl_exec ( $ch );
        curl_close ( $ch );
        return $result;    
    }


}

$Pusher =  new PushNotification();

if(isset($_GET['operation'])){
    echo $aviso->{$_GET['operation']}();
}
else if(isset($_POST['operation'])){

    $data = json_decode($_POST['data'],true);
    echo json_encode($Pusher->{$_POST['operation']}($data));
}
else{
    exit("operação invalida");
}

?>