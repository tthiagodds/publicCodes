<?php
session_start();
//var_dump($_POST);

//Função para Gravar o arquivo no Diretorio
function GravarArquivo($Ga_Conteudo, $Ga_Diretorio, $Ga_Nome, $Ga_Extensao)
{
  unlink($Ga_Diretorio . "\\" . $Ga_Nome . "." . $Ga_Extensao);
  //Variável arquivo armazena o nome e extensão do arquivo.
  $arquivo = $Ga_Diretorio . "\\" . $Ga_Nome . "." . $Ga_Extensao;
  //Variável $fp armazena a conexão com o arquivo e o tipo de ação.
  $fp = fopen($arquivo, "a+");
  //Escreve no arquivo aberto.
  fwrite($fp, $Ga_Conteudo);
  //Fecha o arquivo.
  fclose($fp);
}


/*
 * *****************************************************************************************************
 * *****************************************************************************************************
 * Executa a ação apenas se o numero da pasta e o codigo do log foi enviado
 * *****************************************************************************************************
 */
if (($_POST['JSONCODE'] > 0) && ($_POST['PASTA'] >= 0)) {

  $codigoLog          = $_POST['JSONCODE'];
  $numeroPasta        = $_POST['PASTA'];
  $criarLog           = $_POST['ArquivoLog'];
  $ArquivoTempo       = $_POST['ArquivoTempo'];
  $ArquivoPassVoo     = $_POST['ArquivoPassVoo'];

  $diretorio          = "C:\\Users\\Thiago Silva\\Documents\\AnaliseLogs\\";

  //Cria a pasta onde será salvo o conteudo
  mkdir($diretorio . $numeroPasta);

  /*
 * *****************************************************************************************************
 * *****************************************************************************************************
 * Criar Arquivo de Log
 * *****************************************************************************************************
 */
  if ($criarLog == "on") {
    $nomeArquivoTipo = 'Log';
    $nomeArquivoFinal = $nomeArquivoTipo . '_' . $numeroPasta.'.txt';
    GravarArquivo($codigoLog, $diretorio . $numeroPasta, $nomeArquivoTipo . '_' . $numeroPasta, 'txt');
    $htmlCodigoFormatado = '<hr/><textarea name="" id="" onClick="this.select(); document.execCommand(&apos;copy&apos;)" style="width:100%;" cols="30" rows="10">' . $codigoLog . '</textarea>';

    $handle = fopen($diretorio.$numeroPasta.'\\'.$nomeArquivoTipo.'_'.$numeroPasta.'.txt', 'r');
    if($handle) {
      while (!feof($handle)) {
        $row = fgets($handle);
        if (str_contains($row, 'CreateReservation ->')) {
          //Quando envia dados
          $dadosJson          = explode('->', $row);
          $nomeArquivoTipo    = 'JsonEnviado---';
          $dadosJsonAirReservations = json_decode($dadosJson[1])->Reservation->AirReservations[0];
          $dadosJsonMessages   = json_decode($dadosJson[1])->Messages;
        //Formata os dados em json
          $dadosJsonSaida        = json_decode($dadosJson[1]);
        //Gera o nome do arquivo de acordo com a data, hora e tipo de evento
          $nomeArquivo          = explode('[DEBUG]', $dadosJson[0]);
          $nomeArquivoData      = explode(' ', $nomeArquivo[0]);
        //pego a data
          $nomeArquivoHora      = explode('.', $nomeArquivoData[1]);
        //pego a hora
          $nomeArquivoProvider  = $nomeArquivo[1];

          if($ArquivoTempo){
            $NomeArquivoTempo = str_replace(':', '', $nomeArquivoHora[0]).'_'.substr($nomeArquivoHora[1], 0, 4);
          } else {
            $NomeArquivoTempo = str_replace(':', '', $nomeArquivoHora[0]);
          }

          $nomeArquivoFinal     = $nomeArquivoData[0] . '_' . $NomeArquivoTempo . '--' . str_replace(' ', '-', trim($nomeArquivoProvider)).'_'.$nomeArquivoTipo;

        //Cria o textarea com o conteudo formatado
          $htmlCodigoFormatado = '<hr/><textarea onClick="this.select(); document.execCommand(&apos;copy&apos;)" style="width:100%; font-size:9pt; padding:10px; color:blue;" cols="30" rows="10">' . json_encode($dadosJsonSaida, JSON_PRETTY_PRINT) . '</textarea>';

        //Salva o arquivo json no diretorio
          GravarArquivo(json_encode($dadosJsonSaida, JSON_PRETTY_PRINT), $diretorio . $numeroPasta, $nomeArquivoFinal, 'json');


    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados do PNR e escreve no html
          if ($dadosJsonAirReservations->LocatorCode) {
            $htmldados .= '
            <div class="alert alert-success" role="alert">
              O PNR <span class="badge badge-success p-2"> ( ' . $dadosJsonAirReservations->LocatorCode . ' ) </span> foi criado!
            </div>';
          }
    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html

          if ($dadosJsonAirReservations->OwningPCC) {
            $htmldados .= '
            <div class="alert alert-primary" role="alert">
              Agência: "' . $dadosJsonAirReservations->Contacts[0]->Data . '" <span class="alert-link">PCC: "' . $dadosJsonAirReservations->OwningPCC . '" - "SEM/' . $dadosJsonAirReservations->OwningPCC . '/AG"</span>
            </div>
            ';
          } else {
            if ($dadosJsonAirReservations->Contacts[0]->Data) {
              if ($dadosJsonAirReservations->AttachRule[0]->PCC) {
                $htmldadosPCC = 'PCC: "' . $dadosJsonAirReservations->AttachRule[0]->PCC . '" - "SEM/' . $dadosJsonAirReservations->AttachRule[0]->PCC . '/AG" (' . $dadosJsonAirReservations->AttachRule[0]->RuleName . ') - ';
              }
              $htmldados .= '
              <div class="alert alert-primary" role="alert">
                Agência: "' . $dadosJsonAirReservations->Contacts[0]->Data . '" <span class="alert-link">' . $htmldadosPCC . '</span>
              </div>
              ';
            }
          }

    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html
          if ($dadosJsonAirReservations->Passengers) {
            if (count($dadosJsonAirReservations->Passengers)) {
        //PERCORRE E ESCREVE OS DADOS DO PASSGEIRO
              $htmldados .= '
              <table class="table table-hover table-sm" style="font-size:10pt;">
                <thead>
                  <tr class="thead-light">
                    <th class="text-center" scope="col" colspan="6">Dados dos Passageiros (<strong>' . count($dadosJsonAirReservations->Passengers) . '</strong>)</th>
                  </tr>
                  <tr>
                    <th class="text-center" scope="col">#</th>
                    <th scope="col">Nome</th>
                    <th class="text-center" scope="col">Data Nascimento</th>
                    <th class="text-center" scope="col">Sexo</th>
                    <th class="text-center" scope="col">Idade</th>
                    <th class="text-center" scope="col">Tipo</th>
                  </tr>
                </thead>
                <tbody>
                  ';
                  for ($nPass = 0; $nPass < count($dadosJsonAirReservations->Passengers); $nPass++) {
                    if($dadosJsonAirReservations->Passengers[$nPass]->BirthDate){
                      $DATA_NASC = date('d/m/Y', strtotime($dadosJsonAirReservations->Passengers[$nPass]->BirthDate));
                    } else {
                      $DATA_NASC = '-';
                    }
                    $htmldados .= '
                    <tr>
                      <th class="text-center" scope="row">' . $nPass . '</th>
                      <td>' . $dadosJsonAirReservations->Passengers[$nPass]->FirstName . ' ' . $dadosJsonAirReservations->Passengers[$nPass]->LastName . ' (*-' . $dadosJsonAirReservations->Passengers[$nPass]->LastName . '/' . $dadosJsonAirReservations->Passengers[$nPass]->FirstName . ')</td>
                      <td class="text-center">' . $DATA_NASC . '</td>
                      <td class="text-center">' . $dadosJsonAirReservations->Passengers[$nPass]->Details->Gender . '</td>
                      <td class="text-center">' . $dadosJsonAirReservations->Passengers[$nPass]->Age . '</td>
                      <td class="text-center">' . $dadosJsonAirReservations->Passengers[$nPass]->PTC . '</td>
                    </tr>
                    ';
                  }
                  $htmldados .= '
                </tbody>
              </table>
              ';
            }
          }

    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html
          if ($dadosJsonAirReservations->AirSegments) {
            if (count($dadosJsonAirReservations->AirSegments)) {
        //PERCORRE E ESCREVE OS DADOS DO SEGUIMENTO
              $htmldados .= '
              <table class="table table-hover  table-sm" style="font-size:10pt;">
                <thead>
                  <tr class="thead-light">
                    <th class="text-center" scope="col" colspan="7">Dados dos Seguimentos (<strong>' . count($dadosJsonAirReservations->AirSegments) . '</strong>) </th>
                  </tr>
                  <tr>
                    <th class="text-center" scope="col">#</th>
                    <th  scope="col">Origem</th>
                    <th  scope="col">Destino</th>
                    <th class="text-center" scope="col">CP Aerea</th>
                    <th class="text-center" scope="col">Numero Voo</th>
                    <th class="text-center" scope="col">Aeronave</th>
                    <th class="text-center" scope="col">Smartpoint</th>
                  </tr>
                </thead>
                <tbody>
                  ';
                  for ($nSeguiment = 0; $nSeguiment < count($dadosJsonAirReservations->AirSegments); $nSeguiment++) {
                    $htmldados .= '
                    <tr>
                      <th class="text-center" scope="row">' . $nSeguiment . '</th>
                      <td >' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Origin . ' - ' . date('d/m/Y H:i', strtotime($dadosJsonAirReservations->AirSegments[$nSeguiment]->DepartureTime)) . '</td>
                      <td >' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Destination . ' - ' . date('d/m/Y H:i', strtotime($dadosJsonAirReservations->AirSegments[$nSeguiment]->ArrivalTime)) . '</td>
                      <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->AirV . '</td>
                      <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->FlightNumber . ' </td>
                      <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Equipment . ' </td>
                      <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->AirV . ' ' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->FlightNumber . ' ' . strtoupper(date('d/M', strtotime($dadosJsonAirReservations->AirSegments[$nSeguiment]->DepartureTime))) . ' ' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Origin . '' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Destination . '</td>
                    </tr>
                    ';
                  }
                  $htmldados .= '
                </tbody>
              </table>
              ';
            }
          }

    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html
          if ($dadosJsonMessages) {
            if (count($dadosJsonMessages)) {
        //PERCORRE E ESCREVE OS DADOS DO SEGUIMENTO
              $htmldados .= '
              <table class="table table-hover  table-sm" style="font-size:10pt;">
                <thead>
                  <tr class="thead-light">
                    <th class="text-center" scope="col" colspan="6">Existem <strong>' . count($dadosJsonMessages) . '</strong> Erros neste json</th>
                  </tr>
                  <tr>
                    <th class="text-center" scope="col">#</th>
                    <th  scope="col">Codigo</th>
                    <th  scope="col">Tipo de Mensagem</th>
                    <th  scope="col">Mensagem</th>
                  </tr>
                </thead>
                <tbody>
                  ';
                  for ($nMessage = 0; $nMessage < count($dadosJsonMessages); $nMessage++) {
                    if ($dadosJsonMessages[$nMessage]->MessageType == "Warn") {
                      $MessageColor = 'color:orange;';
                    }
                    if ($dadosJsonMessages[$nMessage]->MessageType == "Error") {
                      $MessageColor = 'color:red;';
                    }
                    $htmldados .= '
                    <tr>
                      <th style="' . $MessageColor . '" class="text-center" scope="row">' . $nMessage . '</th>
                      <td style="' . $MessageColor . '">' . $dadosJsonMessages[$nMessage]->Code . '</td>
                      <td style="' . $MessageColor . '">' . $dadosJsonMessages[$nMessage]->MessageType . '</td>
                      <td style="' . $MessageColor . '">' . $dadosJsonMessages[$nMessage]->Message . '</td>
                    </tr>
                    ';
                  }
                  $htmldados .= '
                </tbody>
              </table>
              ';
            }
          }
        }
        if (str_contains($row, 'CreateReservation <-')) {
          //Quando recebe dados
          $dadosJson           = explode('<-', $row);
          $nomeArquivoTipo     = 'JsonRecebido';
          $dadosJsonAirReservations = json_decode($dadosJson[1])->Data->AirReservations[0];
          $dadosJsonMessages   = json_decode($dadosJson[1])->Messages;
        //Formata os dados em json
          $dadosJsonSaida        = json_decode($dadosJson[1]);

        //Gera o nome do arquivo de acordo com a data, hora e tipo de evento
          $nomeArquivo          = explode('[DEBUG]', $dadosJson[0]);
        $nomeArquivoData      = explode(' ', $nomeArquivo[0]); //pego a data
        $nomeArquivoHora      = explode('.', $nomeArquivoData[1]); //pego a hora
        $nomeArquivoProvider  = $nomeArquivo[1];

        if($ArquivoTempo){
          $NomeArquivoTempo = str_replace(':', '', $nomeArquivoHora[0]).'_'.substr($nomeArquivoHora[1], 0, 4);
        } else {
          $NomeArquivoTempo = str_replace(':', '', $nomeArquivoHora[0]);
        }

        $nomeArquivoFinal     = $nomeArquivoData[0] . '_' . $NomeArquivoTempo . '--' . str_replace(' ', '-', trim($nomeArquivoProvider)).'_'.$nomeArquivoTipo;
        //Cria o textarea com o conteudo formatado
        $htmlCodigoFormatado = '<hr/><textarea onClick="this.select(); document.execCommand(&apos;copy&apos;)" style="width:100%; font-size:9pt; padding:10px; color:blue;" cols="30" rows="10">' . json_encode($dadosJsonSaida, JSON_PRETTY_PRINT) . '</textarea>';
                //Salva o arquivo json no diretorio
        GravarArquivo(json_encode($dadosJsonSaida, JSON_PRETTY_PRINT), $diretorio . $numeroPasta, $nomeArquivoFinal, 'json');

    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados do PNR e escreve no html
        if ($dadosJsonAirReservations->LocatorCode) {
          $htmldados .= '
          <div class="alert alert-success" role="alert">
            O PNR <span class="badge badge-success p-2"> ( ' . $dadosJsonAirReservations->LocatorCode . ' ) </span> foi criado!
          </div>';
        }
    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html

        if ($dadosJsonAirReservations->OwningPCC) {
          $htmldados .= '
          <div class="alert alert-primary" role="alert">
            Agência: "' . $dadosJsonAirReservations->Contacts[0]->Data . '" <span class="alert-link">PCC: "' . $dadosJsonAirReservations->OwningPCC . '" - "SEM/' . $dadosJsonAirReservations->OwningPCC . '/AG"</span>
          </div>
          ';
        } else {
          if ($dadosJsonAirReservations->Contacts[0]->Data) {
            if ($dadosJsonAirReservations->AttachRule[0]->PCC) {
              $htmldadosPCC = 'PCC: "' . $dadosJsonAirReservations->AttachRule[0]->PCC . '" - "SEM/' . $dadosJsonAirReservations->AttachRule[0]->PCC . '/AG" (' . $dadosJsonAirReservations->AttachRule[0]->RuleName . ') - ';
            }
            $htmldados .= '

            <div class="alert alert-primary" role="alert">
              Agência: "' . $dadosJsonAirReservations->Contacts[0]->Data . '" <span class="alert-link">' . $htmldadosPCC . '</span>
            </div>
            ';
          }
        }
    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html
        if ($dadosJsonAirReservations->Passengers) {
          if (count($dadosJsonAirReservations->Passengers)) {
        //PERCORRE E ESCREVE OS DADOS DO PASSGEIRO
            $htmldados .= '
            <table class="table table-hover table-sm" style="font-size:10pt;">
              <thead>
                <tr class="thead-light">
                  <th class="text-center" scope="col" colspan="6">Existem <strong>' . count($dadosJsonAirReservations->Passengers) . '</strong> Passageiros neste json</th>
                </tr>
                <tr>
                  <th class="text-center" scope="col">#</th>
                  <th scope="col">Nome</th>
                  <th class="text-center" scope="col">Data Nascimento</th>
                  <th class="text-center" scope="col">Sexo</th>
                  <th class="text-center" scope="col">Idade</th>
                  <th class="text-center" scope="col">Tipo</th>
                </tr>
              </thead>
              <tbody>
                ';
                for ($nPass = 0; $nPass < count($dadosJsonAirReservations->Passengers); $nPass++) {

                  if($dadosJsonAirReservations->Passengers[$nPass]->BirthDate){
                    $DATA_NASC = date('d/m/Y', strtotime($dadosJsonAirReservations->Passengers[$nPass]->BirthDate));
                  } else {
                    $DATA_NASC = '-';
                  }

                  $htmldados .= '
                  <tr>
                    <th class="text-center" scope="row">' . $nPass . '</th>
                    <td>' . $dadosJsonAirReservations->Passengers[$nPass]->FirstName . ' ' . $dadosJsonAirReservations->Passengers[$nPass]->LastName . ' (*-' . $dadosJsonAirReservations->Passengers[$nPass]->LastName . '/' . $dadosJsonAirReservations->Passengers[$nPass]->FirstName . ')</td>
                    <td class="text-center">' . $DATA_NASC . '</td>
                    <td class="text-center">' . $dadosJsonAirReservations->Passengers[$nPass]->Details->Gender . '</td>
                    <td class="text-center">' . $dadosJsonAirReservations->Passengers[$nPass]->Age . '</td>
                    <td class="text-center">' . $dadosJsonAirReservations->Passengers[$nPass]->PTC . '</td>
                  </tr>
                  ';
                }
                $htmldados .= '
              </tbody>
            </table>
            ';
          }
        }

    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html
        if ($dadosJsonAirReservations->AirSegments) {
          if (count($dadosJsonAirReservations->AirSegments)) {
        //PERCORRE E ESCREVE OS DADOS DO SEGUIMENTO
            $htmldados .= '
            <table class="table table-hover  table-sm" style="font-size:10pt;">
              <thead>
                <tr class="thead-light">
                  <th class="text-center" scope="col" colspan="7">Existem <strong>' . count($dadosJsonAirReservations->AirSegments) . '</strong> Seguimentos neste json</th>
                </tr>
                <tr>
                  <th class="text-center" scope="col">#</th>
                  <th  scope="col">Origem</th>
                  <th  scope="col">Destino</th>
                  <th class="text-center" scope="col">CP Aerea</th>
                  <th class="text-center" scope="col">Numero Voo</th>
                  <th class="text-center" scope="col">Aeronave</th>
                  <th class="text-center" scope="col">Smartpoint</th>
                </tr>
              </thead>
              <tbody>
                ';
                for ($nSeguiment = 0; $nSeguiment < count($dadosJsonAirReservations->AirSegments); $nSeguiment++) {
                  $htmldados .= '
                  <tr>
                    <th class="text-center" scope="row">' . $nSeguiment . '</th>
                    <td >' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Origin . ' - ' . date('d/m/Y H:i', strtotime($dadosJsonAirReservations->AirSegments[$nSeguiment]->DepartureTime)) . '</td>
                    <td >' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Destination . ' - ' . date('d/m/Y H:i', strtotime($dadosJsonAirReservations->AirSegments[$nSeguiment]->ArrivalTime)) . '</td>
                    <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->AirV . '</td>
                    <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->FlightNumber . ' </td>
                    <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Equipment . ' </td>
                    <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->AirV . ' ' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->FlightNumber . ' ' . strtoupper(date('d/M', strtotime($dadosJsonAirReservations->AirSegments[$nSeguiment]->DepartureTime))) . ' ' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Origin . '' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Destination . '</td>
                  </tr>
                  ';
                }
                $htmldados .= '
              </tbody>
            </table>
            ';
          }
        }

    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html
        if ($dadosJsonMessages) {
          if (count($dadosJsonMessages)) {
        //PERCORRE E ESCREVE OS DADOS DO SEGUIMENTO
            $htmldados .= '
            <table class="table table-hover  table-sm" style="font-size:10pt;">
              <thead>
                <tr class="thead-light">
                  <th class="text-center" scope="col" colspan="6">Existem <strong>' . count($dadosJsonMessages) . '</strong> Erros neste json</th>
                </tr>
                <tr>
                  <th class="text-center" scope="col">#</th>
                  <th  scope="col">Codigo</th>
                  <th  scope="col">Tipo de Mensagem</th>
                  <th  scope="col">Mensagem</th>
                </tr>
              </thead>
              <tbody>
                ';
                for ($nMessage = 0; $nMessage < count($dadosJsonMessages); $nMessage++) {
                  if ($dadosJsonMessages[$nMessage]->MessageType == "Warn") {
                    $MessageColor = 'color:orange;';
                  }
                  if ($dadosJsonMessages[$nMessage]->MessageType == "Error") {
                    $MessageColor = 'color:red;';
                  }
                  $htmldados .= '
                  <tr>
                    <th style="' . $MessageColor . '" class="text-center" scope="row">' . $nMessage . '</th>
                    <td style="' . $MessageColor . '">' . $dadosJsonMessages[$nMessage]->Code . '</td>
                    <td style="' . $MessageColor . '">' . $dadosJsonMessages[$nMessage]->MessageType . '</td>
                    <td style="' . $MessageColor . '">' . $dadosJsonMessages[$nMessage]->Message . '</td>
                  </tr>
                  ';
                }
                $htmldados .= '
              </tbody>
            </table>
            ';
          }
        }
      }
    }
  }
  unlink($diretorio.$numeroPasta.'\\_--_Log.json');
}
  /*
 * *****************************************************************************************************
 * *****************************************************************************************************
 * Criar Arquivo json
 * *****************************************************************************************************
 */
  if ((!$criarLog) && ((str_contains($codigoLog, '->')) or (str_contains($codigoLog, '<-')))) {

    if (str_contains($codigoLog, '->')) {
      //Quando envia dados
      $dadosJson          = explode('->', $codigoLog);
      $nomeArquivoTipo    = 'JsonEnviado---';
      $dadosJsonAirReservations = json_decode($dadosJson[1])->Reservation->AirReservations[0];
      $dadosJsonMessages   = json_decode($dadosJson[1])->Messages;
    }
    if (str_contains($codigoLog, '<-')) {
      //Quando recebe dados
      $dadosJson           = explode('<-', $codigoLog);
      $nomeArquivoTipo     = 'JsonRecebido';
      $dadosJsonAirReservations = json_decode($dadosJson[1])->Data->AirReservations[0];
      $dadosJsonMessages   = json_decode($dadosJson[1])->Messages;
    }

    //Formata os dados em json
    $dadosJsonSaida        = json_decode($dadosJson[1]);

    //Gera o nome do arquivo de acordo com a data, hora e tipo de evento
    $nomeArquivo          = explode('[DEBUG]', $dadosJson[0]);
    $nomeArquivoData      = explode(' ', $nomeArquivo[0]); //pego a data
    $nomeArquivoHora      = explode('.', $nomeArquivoData[1]); //pego a hora
    $nomeArquivoProvider  = $nomeArquivo[1];

    if($ArquivoTempo){
      $NomeArquivoTempo = str_replace(':', '', $nomeArquivoHora[0]).'_'.substr($nomeArquivoHora[1], 0, 4);
    } else {
      $NomeArquivoTempo = str_replace(':', '', $nomeArquivoHora[0]);
    }
    $nomeArquivoFinal     = $nomeArquivoData[0] . '_' . $NomeArquivoTempo . '--' . str_replace(' ', '-', trim($nomeArquivoProvider)).'_'.$nomeArquivoTipo;
    //Cria o textarea com o conteudo formatado
    $htmlCodigoFormatado = '<hr/><textarea onClick="this.select(); document.execCommand(&apos;copy&apos;)" style="width:100%; font-size:9pt; padding:10px; color:blue;" cols="30" rows="10">' . json_encode($dadosJsonSaida, JSON_PRETTY_PRINT) . '</textarea>';
    //Salva o arquivo json no diretorio
    GravarArquivo(json_encode($dadosJsonSaida, JSON_PRETTY_PRINT), $diretorio . $numeroPasta, $nomeArquivoFinal, 'json');

    /*
 * *****************************************************************************************************
 * *****************************************************************************************************
 * Gera as saidas de html para tela
 * *****************************************************************************************************
 */

    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados do PNR e escreve no html
    if ($dadosJsonAirReservations->LocatorCode) {
      $htmldados .= '
      <div class="alert alert-success" role="alert">
        O PNR <span class="badge badge-success p-2"> ( ' . $dadosJsonAirReservations->LocatorCode . ' ) </span> foi criado!
      </div>';
      $htmlPassageiroUnico.='TKT: '.$numeroPasta.' | PNR: '.$dadosJsonAirReservations->LocatorCode.' | ';
    }
    if($ArquivoPassVoo){
      $htmlPassageiroUnico.='TKT: '.$numeroPasta.' | PNR: '.$dadosJsonAirReservations->LocatorCode.' | ';
    }

    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html

    if ($dadosJsonAirReservations->OwningPCC) {
      $htmldados .= '
      <div class="alert alert-primary" role="alert">
        Agência: "' . $dadosJsonAirReservations->Contacts[0]->Data . '" <span class="alert-link">PCC: "' . $dadosJsonAirReservations->OwningPCC . '" - "SEM/' . $dadosJsonAirReservations->OwningPCC . '/AG"</span>
      </div>
      ';
    } else {
      if ($dadosJsonAirReservations->Contacts[0]->Data) {
        if ($dadosJsonAirReservations->AttachRule[0]->PCC) {
          $htmldadosPCC = 'PCC: "' . $dadosJsonAirReservations->AttachRule[0]->PCC . '" - "SEM/' . $dadosJsonAirReservations->AttachRule[0]->PCC . '/AG" (' . $dadosJsonAirReservations->AttachRule[0]->RuleName . ') - ';
        }
        $htmldados .= '
        <div class="alert alert-primary" role="alert">
          Agência: "' . $dadosJsonAirReservations->Contacts[0]->Data . '" <span class="alert-link">' . $htmldadosPCC . '</span>
        </div>
        ';
      }
    }


    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html
    if ($dadosJsonAirReservations->Passengers) {
      if (count($dadosJsonAirReservations->Passengers)) {
        //PERCORRE E ESCREVE OS DADOS DO PASSGEIRO
        $htmldados .= '
        <table class="table table-hover table-sm" style="font-size:10pt;">
          <thead>
            <tr class="thead-light">
              <th class="text-center" scope="col" colspan="6">Existem <strong>' . count($dadosJsonAirReservations->Passengers) . '</strong> Passageiros neste json</th>
            </tr>
            <tr>
              <th class="text-center" scope="col">#</th>
              <th scope="col">Nome</th>
              <th class="text-center" scope="col">Data Nascimento</th>
              <th class="text-center" scope="col">Sexo</th>
              <th class="text-center" scope="col">Idade</th>
              <th class="text-center" scope="col">Tipo</th>
            </tr>
          </thead>
          <tbody>
            ';
            for ($nPass = 0; $nPass < count($dadosJsonAirReservations->Passengers); $nPass++) {

              if($dadosJsonAirReservations->Passengers[$nPass]->BirthDate){
                $DATA_NASC = date('d/m/Y', strtotime($dadosJsonAirReservations->Passengers[$nPass]->BirthDate));
              } else {
                $DATA_NASC = '-';
              }

              $htmldados .= '
              <tr>
                <th class="text-center" scope="row">' . $nPass . '</th>
                <td>' . $dadosJsonAirReservations->Passengers[$nPass]->FirstName . ' ' . $dadosJsonAirReservations->Passengers[$nPass]->LastName . ' (*-' . $dadosJsonAirReservations->Passengers[$nPass]->LastName . '/' . $dadosJsonAirReservations->Passengers[$nPass]->FirstName . ')</td>
                <td class="text-center">' . $DATA_NASC . '</td>
                <td class="text-center">' . $dadosJsonAirReservations->Passengers[$nPass]->Details->Gender . '</td>
                <td class="text-center">' . $dadosJsonAirReservations->Passengers[$nPass]->Age . '</td>
                <td class="text-center">' . $dadosJsonAirReservations->Passengers[$nPass]->PTC . '</td>
              </tr>
              ';
              $htmlPassageiroUnico.=$dadosJsonAirReservations->Passengers[$nPass]->LastName . '/' . $dadosJsonAirReservations->Passengers[$nPass]->FirstName.' | ';
            }
            $htmldados .= '
          </tbody>
        </table>
        ';
      }
    }

    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html
    if ($dadosJsonAirReservations->AirSegments) {
      if (count($dadosJsonAirReservations->AirSegments)) {
        //PERCORRE E ESCREVE OS DADOS DO SEGUIMENTO
        $htmldados .= '
        <table class="table table-hover  table-sm" style="font-size:10pt;">
          <thead>
            <tr class="thead-light">
              <th class="text-center" scope="col" colspan="7">Existem <strong>' . count($dadosJsonAirReservations->AirSegments) . '</strong> Seguimentos neste json</th>
            </tr>
            <tr>
              <th class="text-center" scope="col">#</th>
              <th  scope="col">Origem</th>
              <th  scope="col">Destino</th>
              <th class="text-center" scope="col">CP Aerea</th>
              <th class="text-center" scope="col">Numero Voo</th>
              <th class="text-center" scope="col">Aeronave</th>
              <th class="text-center" scope="col">Smartpoint</th>
            </tr>
          </thead>
          <tbody>
            ';
            for ($nSeguiment = 0; $nSeguiment < count($dadosJsonAirReservations->AirSegments); $nSeguiment++) {
              $htmldados .= '
              <tr>
                <th class="text-center" scope="row">' . $nSeguiment . '</th>
                <td >' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Origin . ' - ' . date('d/m/Y H:i', strtotime($dadosJsonAirReservations->AirSegments[$nSeguiment]->DepartureTime)) . '</td>
                <td >' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Destination . ' - ' . date('d/m/Y H:i', strtotime($dadosJsonAirReservations->AirSegments[$nSeguiment]->ArrivalTime)) . '</td>
                <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->AirV . '</td>
                <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->FlightNumber . ' </td>
                <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Equipment . ' </td>
                <td class="text-center">' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->AirV . ' ' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->FlightNumber . ' ' . strtoupper(date('d/M', strtotime($dadosJsonAirReservations->AirSegments[$nSeguiment]->DepartureTime))) . ' ' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Origin . '' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Destination . '</td>
              </tr>
              ';
              $htmlPassageiroUnico.=$dadosJsonAirReservations->AirSegments[$nSeguiment]->Origin . '/' . $dadosJsonAirReservations->AirSegments[$nSeguiment]->Destination.' - '.strtoupper(date('d/M', strtotime($dadosJsonAirReservations->AirSegments[$nSeguiment]->DepartureTime))).' | ';
            }
            $htmldados .= '
          </tbody>
        </table>
        ';
      }
    }

    //*****************************************************************************************************
    //*****************************************************************************************************
    //Gera os dados da PCC e Agencia no html
    if ($dadosJsonMessages) {
      if (count($dadosJsonMessages)) {
        //PERCORRE E ESCREVE OS DADOS DO SEGUIMENTO
        $htmldados .= '
        <table class="table table-hover  table-sm" style="font-size:10pt;">
          <thead>
            <tr class="thead-light">
              <th class="text-center" scope="col" colspan="6">Existem <strong>' . count($dadosJsonMessages) . '</strong> Erros neste json</th>
            </tr>
            <tr>
              <th class="text-center" scope="col">#</th>
              <th  scope="col">Codigo</th>
              <th  scope="col">Tipo de Mensagem</th>
              <th  scope="col">Mensagem</th>
            </tr>
          </thead>
          <tbody>
            ';
            for ($nMessage = 0; $nMessage < count($dadosJsonMessages); $nMessage++) {
              if ($dadosJsonMessages[$nMessage]->MessageType == "Warn") {
                $MessageColor = 'color:orange;';
              }
              if ($dadosJsonMessages[$nMessage]->MessageType == "Error") {
                $MessageColor = 'color:red;';
              }
              $htmldados .= '
              <tr>
                <th style="' . $MessageColor . '" class="text-center" scope="row">' . $nMessage . '</th>
                <td style="' . $MessageColor . '">' . $dadosJsonMessages[$nMessage]->Code . '</td>
                <td style="' . $MessageColor . '">' . $dadosJsonMessages[$nMessage]->MessageType . '</td>
                <td style="' . $MessageColor . '">' . $dadosJsonMessages[$nMessage]->Message . '</td>
              </tr>
              ';
            }
            $htmldados .= '
          </tbody>
        </table>
        ';
      }
    }
  }

  $htmldadosFirst.= '
  <div class="alert alert-warning" role="alert">
    Tipo de dado a ser salvo: <span class="badge badge-secondary p-2">' . $nomeArquivoTipo . '</span>, arquivo criado: <span class="badge badge-secondary p-2">' . $nomeArquivoFinal . '</span>
  </div>
  ';

  //cria uma session para manter o nome da pasta
  $_SESSION['numeroPasta'] = $numeroPasta;
  $_SESSION['diretorio'] = 'Diretorio de Arquivos: '.$diretorio;

}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NPF - Analise</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
  <div class="container" style="margin-top:25px;">
    <div class="card">
      <div class="card-header"><a href=".">Analise de Logs <?=$_SESSION['diretorio'];?></a>
        <div class="btn-group float-right" role="group" aria-label="Button group with nested dropdown">
          <div class="btn-group" role="group">
            <button id="btnGroupDrop1" type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              Padrão Resposta
            </button>
            <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
              <a class="dropdown-item" onclick="padraoResposta('pnrGerado')" data-toggle="modal" data-target="#exampleModal" href="#">PNR Gerado</a>
              <a class="dropdown-item" onclick="padraoResposta('pnrNaoGeradoPnrPosterior')" data-toggle="modal" data-target="#exampleModal" href="#">Sem PNR, PNR Posterior</a>
              <a class="dropdown-item" onclick="padraoResposta('pnrNaoGerado')" data-toggle="modal" data-target="#exampleModal" href="#">Sem PNR, Reserva Amadeus</a>
              <a class="dropdown-item" onclick="padraoResposta('pnrNaoGeradoSemRetornoXml')" data-toggle="modal" data-target="#exampleModal" href="#">Sem PNR, Sem Retorno XML CreateReservation</a>
              <a class="dropdown-item" onclick="padraoResposta('availClosed')" data-toggle="modal" data-target="#exampleModal" href="#">AVAIL/WL CLOSED</a>
              <a class="dropdown-item" onclick="padraoResposta('pluginCP')" data-toggle="modal" data-target="#exampleModal" href="#">Plugin da CP</a>
              <a class="dropdown-item" onclick="padraoResposta('PNRGeradoProvider')" data-toggle="modal" data-target="#exampleModal" href="#">PNR Gerado so Provider</a>
              <a class="dropdown-item" onclick="padraoResposta('pluginTravelInfo')" data-toggle="modal" data-target="#exampleModal" href="#">Plugin TravelInfo</a>
              <a class="dropdown-item" href="/listerros/" target="_blank">Lista de Erros</a>
              <a class="dropdown-item" href="ContadorLinhaLog.php" target="_blank">Contador Linha</a>
            </div>
          </div>
        </div>
      </div>
      <div class="card-body">
        <form action="." method="post">
          <div class="form-group">
            <label for="exampleInputEmail1">Número do Ticket:</label>
            <input type="number" class="form-control" onClick="this.select();" name="PASTA" id="" value="<?= $_SESSION['numeroPasta']; ?>">
          </div>

          <div class="form-group">
            <label for="exampleInputEmail1">Codigo do Log:</label>
            <textarea name="JSONCODE" id="" onClick="this.select();" style="width:100%; font-size:9pt; padding:10px; color:dimgray;" cols="30" rows="5"><?php echo $_POST['JSONCODE']; ?></textarea>
          </div>

          <div class="form-check" style="margin-bottom:15px;">
            <input type="checkbox" class="form-check-input" name="ArquivoLog" id="exampleCheck1">
            <label class="form-check-label" style="cursor:pointer;" for="exampleCheck1">Arquivo de Log</label>

            <input type="checkbox" class="form-check-input" style="margin-left:35px;" name="ArquivoTempo" id="exampleCheck1">
            <label class="form-check-label" style="cursor:pointer;margin-left:55px;" for="exampleCheck1">Tempo no FileName</label>

            <input type="checkbox" class="form-check-input" style="margin-left:35px;" name="ArquivoPassVoo" id="exampleCheck1">
            <label class="form-check-label" style="cursor:pointer;margin-left:55px;" for="exampleCheck1">Dados Pass/Voo</label>
          </div>

          <button type="submit" class="btn btn-primary">Processar Dados</button>
        </form>
        <hr>
        <div class="row">
          <div class="col-md-12">
            <?=$htmlPassageiroUnico; ?>
          </div>

          <div class="col-md-12">
            <?= $htmldadosFirst.$htmldados; ?>
          </div>

          <div class="col-md-12">
            <? if ($nomeArquivoFinal <> $_SESSION['nomeArquivoFinal']) { ?>

            <div id="accordion">
              <div class="card">
                <div class="card-header" id="headingOne">
                  <h5 class="mb-0">
                    <button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                      Dados Anterior
                    </button>
                  </h5>
                </div>

                <div id="collapseOne" class="collapse " aria-labelledby="headingOne" data-parent="#accordion">
                  <div class="card-body">
                    <? echo $_SESSION['htmldados']; ?>
                  </div>
                </div>
              </div>
            </div>
            <?
          }
          ?>
        </div>
      </div>
      <?= $htmlCodigoFormatado; ?>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
        <!--<button type="button" class="btn btn-primary">Copiar Texto</button>-->
      </div>
    </div>
  </div>
</div>


<script>
  <?
  if ($dadosJsonAirReservations->LocatorCode) {
    echo 'let numPNR  = "' . $dadosJsonAirReservations->LocatorCode . '"';
  } else {
    echo 'let numPNR  = "XXXXXX"';
  }
  ?>
  function padraoResposta(param) {
    let textoResposta = ''
    let textoTitulo = ''
    if (param == 'pnrGerado') {
      textoTitulo = 'PNR Gerado'
      textoResposta += '<p><strong>Não há erros na Criação da Reserva!</strong></p><p><br data-cke-filler="true"></p><p>Foi encaminhando um CreateReservation, com o seu devido retorno do arquivo .xml do CreateReservation, conforme <strong>dados em anexo</strong>.</p><pre data-language="Plain text" spellcheck="false"><code class="language-plaintext">O PNR criado é: "' + numPNR + '", que consta como: "CONFIRMADO",</code></pre><p>Também foi checado na <i>Plataforma NPF (<u>últimos PNR</u>)</i> e no <i>TravelPort Smartpoint</i> filtrando pelo nome do passageiro e não há outras reservas para o mesmo trajeto/datas.</p><p><br data-cke-filler="true"></p><p><i><strong>Thiago Silva</strong></i></p><p><br data-cke-filler="true"></p><p><br data-cke-filler="true"></p>'
    } else if (param == 'pnrNaoGeradoPnrPosterior') {
      textoTitulo = 'Sem PNR, PNR Posterior'
      textoResposta += '<p>O PNR NAO FOI CRIADO!</p>'
      textoResposta += '<p>Foi encaminhando um CreateReservation, porem não se tem o retorno do .xml</p>'
      textoResposta += '<p>Analisando o Smartpoint e na Plataforma NPF, foi identificado o PNR: 3743H3 para o mesmo passageiro, que foi criado normalmente as 11:57, conforme entrada posterior do log.</p>'
      textoResposta += '<p>2022-12-12 11:57:15.2699 (33) [DEBUG] UniversalAPI CreateReservation <-</p>'
      textoResposta += '<p>Thiago Silva</p>'
    } else if (param == 'availClosed') {
      textoTitulo = 'Um ou mais segmentos não podem ser reservados'
      textoResposta += '<p><strong><u>O PNR NAO FOI CRIADO!</u></strong></p><p><br data-cke-filler="true"></p><p>Foi encaminhando um CreateReservation, porem não se tem o retorno do arquivo .xml do CreateReservation.</p><pre data-language="Plain text" spellcheck="false"><code class="language-plaintext">Conforme orientação recebida do provider, um dos seguimentos não esta disponivel para reserva em alguma das datas informadas pelo agente.</code></pre><p>Também foi checado na <i>Plataforma NPF (<u>últimos PNR</u>)</i> e no <i>TravelPort Smartpoint</i> filtrando pelo nome do passageiro e não há outras reservas para o mesmo trajeto/datas.</p><p><br data-cke-filler="true"></p><p><strong>Thiago Silva</strong></p>'
    } else if (param == 'pluginCP') {
      textoTitulo = 'Envio do Lnink de Downalod da CP'
      textoResposta += '<p>Olá, XXX</p><p><br data-cke-filler="true"></p><p>Lembrando que: <i><strong><u>esta versão do plugin da CP só pode ser instalada após a instalação com sucesso do Smartpoint 11.1</u></strong></i></p><p><br data-cke-filler="true"></p><p>Segue link para download:</p><p><a target="_blank" rel="noopener noreferrer" href="https://drive.google.com/file/d/1EvhwWLErZtmfcL2cS6IxcYtTREUtw5yr/view?usp=share_link">https://drive.google.com/file/d/1EvhwWLErZtmfcL2cS6IxcYtTREUtw5yr/view?usp=share_link</a><br><br><i><strong>Thiago Silva</strong></i></p>'
    } else if (param == 'pluginTravelInfo') {
      textoTitulo = 'Envio do Lnink de Downalod do TravelInfo'
      textoResposta += '<p>Olá, XXX</p><p><br data-cke-filler="true"></p><p>Conforme solicitado segue o link para download da ultima versão do TravelInfo:</p><p><a target="_blank" rel="noopener noreferrer" href="https://drive.google.com/file/d/175L0jRcW3XlkEsEKHaSoCfxcnS49u6j0/view?usp=share_link">https://drive.google.com/file/d/175L0jRcW3XlkEsEKHaSoCfxcnS49u6j0/view?usp=share_link</a><br><br><i><strong>Thiago Silva</strong></i></p>'
    } else if (param == 'pnrNaoGerado') {
      textoTitulo = 'Sem PNR, Reserva Amadeus'
      textoResposta += '<p>O PNR NAO FOI CRIADO!</p>'
      textoResposta += '<p>Foi encaminhando um CreateReservation, porem não se tem o retorno do .xml, (RESERVA AMADEUS), conforme dados em anexo.</p>'
      textoResposta += '<p>Verificado na Plataforma NPF e no SmartPoint e não há outras reservas  ATIVAS  do mesmo passageiro, no mesmo trajeto e datas;</p>'
      textoResposta += '<p>Thiago Silva</p>'
    } else if (param == 'pnrNaoGeradoSemRetornoXml') {
      textoTitulo = 'Sem PNR, Sem Retorno XML CreateReservation'
      textoResposta += '<p><strong><u>O PNR NAO FOI CRIADO!</u></strong></p><p><br data-cke-filler="true"></p><p>Foi encaminhando um CreateReservation, porem não se tem o retorno do arquivo .xml do CreateReservation.</p><p>Foi checado na <i>Plataforma NPF (<u>últimos PNR</u>)</i> e no <i>TravelPort Smartpoint</i>, filtrando pelo nome do passageiro e não há outras reservas do mesmo passageiro para o mesmo trajeto/datas.</p><p><br data-cke-filler="true"></p><p><strong>Thiago Silva</strong></p><p><br data-cke-filler="true"></p><p><br data-cke-filler="true"></p>'
    }
    else if (param == 'PNRGeradoProvider') {
      textoTitulo = 'O PNR foi gerado apenas no Provider'
      textoResposta += '<p><strong><u>O PNR FOI GERADO APENAS NO PROVIDER!</u></strong></p><p><br data-cke-filler="true"></p><p>Foi encaminhando um CreateReservation, com seu devido retorno dos arquivos .xml do CreateReservation.</p><pre data-language="Plain text" spellcheck="false"><code class="language-plaintext"><?=$htmlPassageiroUnico;?></code></pre><p>Foi identificado no retorno da solicitação a criação do PNR, e checado na <i>Plataforma NPF (<u>pesquisa PNR</u>)</i> e não foi identificado o mesmo.</p><p><br data-cke-filler="true"></p><p><strong>Thiago Silva</strong></p><p><br data-cke-filler="true"></p>'
    }
    document.querySelector('div.modal-body').innerHTML = textoResposta
    document.querySelector('h5#exampleModalLabel').innerHTML = textoTitulo
  }
</script>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script>
    //let pnr = window.prompt('digite o numero do PNR')
  </script>
</body>
</html>
<?
$_SESSION['htmldados'] = $htmldados;
$_SESSION['nomeArquivoFinal'] = $nomeArquivoFinal;
?>