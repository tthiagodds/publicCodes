<?php


$MYSQL_HOST = 'localhost';
$MYSQL_USER = 'root';
$MYSQL_PASS = '';
$MYSQL_DB   = 'sisges04_sisgest';

try {
 $MYSQL = new PDO("mysql:host=$MYSQL_HOST;dbname=$MYSQL_DB;charset=utf8", $MYSQL_USER, $MYSQL_PASS);
 //echo "CONECTION MYSQL CONECTED.<br />";
}catch(PDOException $e){
 echo "MYSQL Error: Connection could not be established.<br />Motivo: ".$e->getMessage() ;
}




/*
$file = "tv_channels_tthiagodds_plus.m3u";

$myfile = fopen($file, "r") or die("Unable to open file!");
echo fread($myfile,filesize($file));
fclose($myfile);
*/

$arquivo = file('tv_channels_tthiagodds_plus.m3u');



// Percorre o array, mostrando o número da linha que está
foreach ($arquivo as $linhaNumero => $linhaInfo) {
    if($linhaNumero>1){
        if($linhaNumero % 2 == 0){
            //linha Par
            $DadosFinal1.= 'tvg-midia="'.trim($linhaInfo).'" ';
        } else {
            $DadosFinal1.= str_replace('#EXTINF:-1 ','',str_replace('",','" tvg-name2="',$linhaInfo));
        }
    }
}
$DadosFinal2 = str_replace('tvg-midia="','',$DadosFinal1);
$DadosFinal2 = str_replace('" tvg-id="','>',$DadosFinal2);
$DadosFinal2 = str_replace('" tvg-name="','>',$DadosFinal2);
$DadosFinal2 = str_replace('" tvg-logo="','>',$DadosFinal2);
$DadosFinal2 = str_replace('" group-title="','>',$DadosFinal2);
$DadosFinal2 = str_replace('" tvg-name2="','>',$DadosFinal2);


/**
 

 $arquivo = "meu_arquivo.txt";
	//Variável $fp armazena a conexão com o arquivo e o tipo de ação.
	$fp = fopen($arquivo, "a+");
	//Escreve no arquivo aberto.
	fwrite($fp, $DadosFinal2);
	//Fecha o arquivo.
	fclose($fp);
  
 
 */


 $arquivo = file('meu_arquivo.txt');



 // Percorre o array, mostrando o número da linha que está
 foreach ($arquivo as $linhaInfo) {


	$linhaInfo = trim($linhaInfo);
	//Colocar em um array cada item separado pela virgula na string
	$valor = explode('>', $linhaInfo);


	$tvg_midia      = $valor[0];
	$tvg_id         = $valor[1];
	$tvg_name       = $valor[2];
	$tvg_logo       = $valor[3];
	$group_title    = $valor[4];


    echo $valor[0].$valor[1].$valor[2].$valor[3].$valor[4];

    $MYSQL->exec("INSERT INTO midia_iptv_lista  (tvg_id,tvg_name,tvg_logo,group_title,tvg_midia)  VALUES  ('$tvg_id','$tvg_name','$tvg_logo','$group_title','$tvg_midia') ");

 }


 
 
 
 
 








 

//$DadosFinal = $DadosFinal2;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>js.Mudando Conteudo de Elementos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
</head>
<body>
    <div class="container-fluid">
    <div class="card mt-2">
        <div class="card-header" onclick="mudarTitulo('div.card-header');">
          Featured
        </div>
        <div class="card-body">
          <p class="card-text" style="font-size:8pt;"><?=$DadosFinal;?></p>
        </div>
      </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
</body>
</html>