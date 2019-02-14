<?php

require_once("src/PHPMailer.php");
require_once("src/SMTP.php");
require_once("src/Exception.php");
use PHPMailer\PHPMailer\PHPMailer;

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
    self::$instance->mail->setLanguage("br");
    self::$instance->mail->CharSet = "UTF-8";
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

  private function MessageInsert($data)
  {
    self::$instance->mail->Subject = "Cadastro Realizado";//Assunto do e-mail
    self::$instance->mail->MsgHTML("
      <h1>Bem vindo ao Doctor 100</h1>
      <p><b>" . $data['Nome'] . "</b> obrigado por se cadastrar na nossa plataforma, para finalizar sua inscricao e confirmar
      sua identidade é preciso que voce clique no link abaixo para validarmos seu email.</p>
      <p>" . $data['Link'] . "</p>
      ");
  }

  private function MessageValidate($data)
  {
    self::$instance->mail->Subject = "Email Verificado";//Assunto do e-mail
    self::$instance->mail->MsgHTML("
        <h1>Email verificado com sucesso</h1>
        <p><b>" . $data['Nome'] . "</b>, agora você pode começar a usar nossa plataforma.</p>
        ");
  }

  private function MessageUpdate($data)
  {
    self::$instance->mail->Subject = "Dados Atualizados";//Assunto do e-mail
    self::$instance->mail->MsgHTML("
        <h1>Dados atualizados com sucesso</h1>
        <p><b>" . $data['Nome'] . "</b>, obrigado por usar nossa plataforma.</p>
        ");
  }

  private function SendCreateAccountEmail($data)
  {
  //add the image as an attachmente
      self::$instance->mail->Subject = "Software 2 cans inscrição";//Assunto do e-mail
      self::$instance->mail->MsgHTML("
      <h1>Cadastro realizado com sucesso</h1>
      <p><b>" . $data['Email'] . "</b>, obrigado por se inscrever no nosso aplicativo.</p>
      <p>Clique no link abaixo para poder confirmar a atenticidade da inscrição</P>
      <a href=" . $data['link'] . " target='_blank'>Link de para confirmar sua inscrição</a>
      ");
  }

  private function PagSeguroNotification($data)
  {
  //add the image as an attachmente
      self::$instance->mail->Subject = "Software 2 cans ";//Assunto do e-mail
      self::$instance->mail->MsgHTML("
  <h1>".json_encode($data['data'])."</h1>
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
      self::$instance->mail->isHTML(true);  
      self::$instance->mail->SetFrom("2cans.software@gmail.com", "2cans "); //Seu e-mail
      self::$instance->mail->AddReplyTo("2cans.software@gmail.com", "2cans"); //Seu e-mail
      self::$instance->mail->AddAddress($data['Email']);
 
     //Define o corpo do email
      switch ($data['MailOperation']) {
        case ('CreateUser'):
          self::$instance->SendCreateAccountEmail($data);
          break;
        case ('notification'):
          self::$instance->PagSeguroNotification($data);
          break;
        case ("CreateMedicoUser"):
            self::$instance->SendCreateAccountEmail($data);
            break;
        case ('Insert'):
          self::$instance->MessageInsert($data);
          break;
        case ('Validate'):
          self::$instance->MessageValidate($data);
          break;
        case ('Update'):
          self::$instance->MessageUpdate($data);
          break;
        case ("QrCode"):
          self::$instance->SendQrCode($data);
          break;
        default:
          exit("Operação Inválida");
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
    } catch (Exception  $e) {
      return array(
        "code" => 1,
        "message" => $e->errorMessage()
      );
    }
    catch(Exception $e){
      return array(
        "code" => 1,
        "message" => $e->getMessage()
      );
    }
  }

  private function GetDomain($toemail){
    $email_arr = explode('@', $toemail);
    $domain = array_slice($email_arr, -1);
    $domain = $domain[0];

    // Trim [ and ] from beginning and end of domain string, respectively
    $domain = ltrim($domain, '[');
    $domain = rtrim($domain, ']');

    return $domain;
}

  private function RemoveIPv6($domain){
      if ('IPv6:' == substr($domain, 0, strlen('IPv6:'))) {
          $domain = substr($domain, strlen('IPv6') + 1);
      }
      return $domain;
  }

  private function CheckForIP($domain){
      // Check if the domain has an IP address assigned to it
      print(filter_var($domain, FILTER_VALIDATE_IP));
      if(filter_var($domain, FILTER_VALIDATE_IP))
          return $domain;
      return "";
  }

  private function GetClosestHost($mxhosts,$mxweight){
      return $mxhosts[array_search(min($mxweight), $mxhosts)];
  }

  private function GetRecordA($domain){
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
      $fromemail = "2cans.software@gmail.com";
      $domain = self::GetDomain($toemail);
      $domain = self::RemoveIPv6($domain);
      $mxhosts = [];
      $mxweight = 0;
      $details = "";
      $mx_ip = self::CheckForIP($domain);
      if (empty($mx_ip)) {
        getmxrr($domain, $mxhosts, $mxweight);
      }
      if (!empty($mxhosts)){
          $mx_ip = self::GetClosestHost($mxhosts, $mxweight);
      }
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
      $connect = fsockopen($mx_ip, 587);
      if (!$connect) {
          $details .= "Invalid, could not connect to the server";
          return 0;
      }
  // Initiate the Mail Sending SMTP transaction
      if (!preg_match('/^220/i', $out = fgets($connect, 1024)))
          return 0;
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