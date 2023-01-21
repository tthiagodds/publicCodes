<?
ini_set('memory_limit', '256M');
/*
$diretorio = 'C:\Users\Thiago Silva\Documents\AnaliseLogs\logs\Ivolfy';
$file = '2023-01-03.txt';
*/

$diretorio = 'C:\xampp\htdocs';
$file = '2023-01-04.txt';

$maiorTamanho = 898309;

$handle = fopen($diretorio.'\\'.$file, 'r');

$LinhasMaiores.='Aqui estao os arquivos que possuem tamanho maiores que '.$maiorTamanho.', os dados estao delimitados por ;'.PHP_EOL;
$LinhasMaiores.='========================================================================================================='.PHP_EOL;
$linha.='Aqui estao as linha do arquivo com seu respectivos tamanhos, os dados estao delimitados por ;'.PHP_EOL;
$linha.='============================================================================================='.PHP_EOL;
$linha.='Linha;Numero da Linha;T.Caracter;Quantidade de Caracteres'.PHP_EOL;

$nlinha = 0;
if($handle) {
    while (!feof($handle)) {
      $nlinha++;
        $row = fgets($handle);
        $linha.= 'L;'.$nlinha.';T;'.strlen($row).PHP_EOL;
        if(strlen($row)>$maiorTamanho){
          $LinhasMaiores.='L;'.$nlinha.';T;'.strlen($row).';'.substr($row, 0, 60).PHP_EOL;
        }
    }
}
unlink('Ivolfy_'.$file.'_TamanhoLinhas.csv');
unlink('Ivolfy_'.$file.'_TamanhoLinhasComConteudo.txt');
$fp = fopen('Ivolfy_'.$file.'_TamanhoLinhas.csv', "a+");
//Escreve no arquivo aberto.
fwrite($fp, $linha);
//Fecha o arquivo.
fclose($fp);
$fp = fopen('Ivolfy_'.$file.'_TamanhoLinhasComConteudo.txt', "a+");
//Escreve no arquivo aberto.
fwrite($fp, $LinhasMaiores);
//Fecha o arquivo.
fclose($fp);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
  
</body>
</html>