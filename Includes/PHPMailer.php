<?php

require_once("class.phpmailer.php");
header('Content-type: text/html; charset=utf-8');
setlocale(LC_ALL, null); // limpa com defaults do sistema... não precisa.
setlocale(LC_ALL, 'pt_BR.utf-8'); // acho mais correto.

class Email
{
    private static $instance = "";
    private $mail = "";

    public static function GetInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Email();
            self::$instance->CreatePHPMailer();
        }
        return self::$instance;
    }

    private function CreatePHPMailer()
    {
        self::$instance->mail = new PHPMailer(true); // Inicia a classe PHPMailer
        self::$instance->mail->IsSMTP();  // Define que a mensagem será SMTP
    }

    private function GetConfigData()
    {
        $config = parse_ini_file("../Configs/PHPMailerConfig.ini");
        $mailConfig = array(
            'SMTPDebug' => $config['SMTPDebug'],
            'SMTPAuth' => $config['SMTPAuth'],
            'SMTPSecure' => $config['SMTPSecure'],
            'Host' => $config['Host'],
            'Port' => $config['Port'],
            'Username' => $config['Username'],
            'Password' => $config['Password']
        );
        return $mailConfig;
    }



    private function SendQrCode($data)
    {
        $imageSrc = explode(",", $data['QrCode']);
        $based = $imageSrc[1];
    //add the image as an attachmente
        self::$instance->mail->Subject = "iNOVATECH equipe";//Assunto do e-mail
        self::$instance->mail->AddStringAttachment(base64_decode($based), "QrCode.png", "base64", "image/png");
        self::$instance->mail->MsgHTML("
    <h1>Inscrição realizada com sucesso</h1>
    <p><b>" . $data['Nome'] . "</b>, obrigado por se inscrever no evento.</p>
    <p>Esse QrCode será utilizado para computar sua presença no evento</P>
    <p>A perda deste QrCode impossibilita a organização de confirmar sua presença</P>
    <p style='background:red;'>Por favor evite responder esse email</P>
    ");

    }

    private function SendCreateAccountEmail($data)
    {
    //add the image as an attachmente
        self::$instance->mail->Subject = "CDT UNB ";//Assunto do e-mail
        self::$instance->mail->MsgHTML("
    <h1>Cadastro realizado com sucesso</h1>
    <p><b>" . $data['Email'] . "</b>, obrigado por se inscrever no nosso aplicativo.</p>
    <p>Clique no link abaixo para poder confirmar a atenticidade da inscrição</P>
    <a href=" . $data['link'] . " target='_blank'>Link de confirmação de inscrição</a>
    ");
    }

    private function PasswordRecovery($data)
    {
    //add the image as an attachmente
        self::$instance->mail->Subject = "CDT UNB ";//Assunto do e-mail
        self::$instance->mail->MsgHTML("
    <h1>Recuperação de senha feita com sucesso.</h1>
    <p>sua nova senha é:".$data['password']."</p>
    ");
    }

    private function SendNotificaion($data)
    {
    //add the image as an attachmente
        self::$instance->mail->Subject = "CDT UNB ";//Assunto do e-mail
        self::$instance->mail->MsgHTML("
    <h1>Portal iae</h1>
    <p>" . $data['mensagem'] . "</p>
    ");
    }

    public function SendEmail($data)
    {
        try {
            $PHPMailerConfig = self::$instance->GetConfigData();
            $password = $PHPMailerConfig['Password'];
            self::$instance->mail->SMTPDebug = $PHPMailerConfig['SMTPDebug']; // Debugar: 1 = erros e mensagens, 2 = mensagens apenas
            self::$instance->mail->SMTPAuth = $PHPMailerConfig['SMTPAuth']; // Autenticação requerida pelo servidor
            self::$instance->mail->SMTPSecure = $PHPMailerConfig['SMTPSecure'];  // Certicado requerido pelo provedor (ssl,tsl)
            self::$instance->mail->Host = $PHPMailerConfig['Host']; // SMTP utilizado
            self::$instance->mail->Port = $PHPMailerConfig['Port']; // Porta utilizada pelo provedor
            self::$instance->mail->Username = $PHPMailerConfig['Username']; // Usuário do servidor SMTP (endereço de email)
            self::$instance->mail->Password = $PHPMailerConfig['Password']; // Senha do servidor SMTP (senha do email usado)
            self::$instance->mail->Charset = 'UTF-8';
      //Define o remetente
      // =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=    
      //self::$instance->mail->SetFrom("contato@2cans.com.br", "2 CANS"); //Seu e-mail
            self::$instance->mail->SetFrom("unb.cdt@gmail.com", "CDT UNB"); //Seu e-mail
            self::$instance->mail->AddReplyTo("unb.cdt@gmail.com", "CDT UNB"); //Seu e-mail
      //self::$instance->mail->Subject = "Teste PHP Mailer";//Assunto do e-mail
 
 
     //Define os destinatário(s)
     //=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
            self::$instance->mail->AddAddress($data['Email']);
//    self::$instance->mail->AddAddress("2cans.software@gmail.com");

     //self::$instance->mail->AddAddress("vinifragam@gmail.com");
     //Campos abaixo são opcionais 
     //=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
     //$mail->AddCC('destinarario@dominio.com.br', 'Destinatario'); // Copia
     //$mail->AddBCC('destinatario_oculto@dominio.com.br', 'Destinatario2`'); // Cópia Oculta
    //  self::$instance->mail->AddAttachment("Inovatech.png");      // Adicionar um anexo
    //  self::$instance->mail->AddEmbeddedImage("Inovatech.png", "Inovatech");
 
     //Define o corpo do email
            switch ($data['MailOperation']) {
                case 'CreateUser':
                    self::$instance->SendCreateAccountEmail($data);
                    break;
                case "SendNotification":
                    self::$instance->SendNotificaion($data);
                    break;
                case "PasswordRecovery":
                    self::$instance->PasswordRecovery($data);
                    break;
                default:
                    return array(
                        "code" => 1,
                        "message" => "operação invalida"
                    );
            }
      
     //
     ////Caso queira colocar o conteudo de um arquivo utilize o método abaixo ao invés da mensagem no corpo do e-mail.
     //$mail->MsgHTML(file_get_contents('arquivo.html'));

            self::$instance->mail->Send();
            return array(
                "code" => 0,
                "message" => "Email enviado com suceso"
            );
    //caso apresente algum erro é apresentado abaixo com essa exceção.
        } catch (phpmailerException $e) {
            return $e->errorMessage(); //Mensagem de erro costumizada do PHPMailer'
        }
    }

    private function GetDomain($toemail)
    {
        $email_arr = explode('@', $toemail);
        $domain = array_slice($email_arr, -1);
        $domain = $domain[0];

    // Trim [ and ] from beginning and end of domain string, respectively
        $domain = ltrim($domain, '[');
        $domain = rtrim($domain, ']');

        return $domain;
    }

    private function RemoveIPv6($domain)
    {
        if ('IPv6:' == substr($domain, 0, strlen('IPv6:'))) {
            $domain = substr($domain, strlen('IPv6') + 1);
        }
        return $domain;
    }

    private function CheckForIP($domain)
    {
      // Check if the domain has an IP address assigned to it
        if (filter_var($domain, FILTER_VALIDATE_IP))
            return $domain;
        return "";
    }

    private function GetClosestHost($mxhosts, $mxweight)
    {
        return $mxhosts[array_search(min($mxweight), $mxhosts)];
    }

    private function GetRecordA($domain)
    {
        if (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            $record_a = dns_get_record($domain, DNS_A);
        elseif (filter_var($domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
            $record_a = dns_get_record($domain, DNS_AAAA);
        return empty($record_a);
    }

  // if you can refact this code please do it, i'm tired 
  // i have refactored enough
    public function CheckMailExistence($toemail)
    {
        $fromemail = "unb.cdt@gmail.com";
        $domain = self::GetDomain($toemail);
        $domain = self::RemoveIPv6($domain);

        $mxhosts = [];
        $details = "";

        $mx_ip = self::CheckForIP($domain);

        if (empty($mx_ip)) {
            getmxrr($domain, $mxhosts, $mxweight);
        }

        if (!empty($mxhosts))
            $mx_ip = self::GetClosestHost($mxhosts, $mxweight);

        else {
            $record_a = self::GetRecordA($domain);
            if (!empty($record_a))
                $mx_ip = $record_a[0]['ip'];
            else {
                $details .= "Invalid e-mail, No suitable MX records found";
                return 0;
            }
        }

    // Open a socket connection with the hostname, smtp port 25
        $connect = @fsockopen($mx_ip, 25);
        $details = "";

        if (!$connect) {
            $details .= "Invalid, could not connect to the server";
            return 0;
        }

    // Initiate the Mail Sending SMTP transaction
        if (!preg_match('/^220/i', $out = fgets($connect, 1024)))
            return "Invalid, Server refused to answer";

        fputs($connect, "HELO $mx_ip\r\n");
        $out = fgets($connect, 1024);
        $details .= $out . "\n";

        fputs($connect, "MAIL FROM: <$fromemail>\r\n");
        $from = fgets($connect, 1024);
        $details .= $from . "\n";

        fputs($connect, "RCPT TO: <$toemail>\r\n");
        $to = fgets($connect, 1024);
        $details .= $to . "\n";

        fputs($connect, 'QUIT');
        fclose($connect);
    // The expected response is 250 if the email is valid
        if (!preg_match('/^250/i', $from) || !preg_match('/^250/i', $to)) {
            $details .= "Invalid, The email account that you tried to reach does not exist";
            return 0;
        }

        return 1;
    }
}

?>