<?php
	/** YURA */
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	header('Content-Type: text/html; charset=UTF-8');
  	header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
  	header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
  	header("Cache-Control: no-store, no-cache, must-revalidate");
  	header("Cache-Control: post-check=0, pre-check=0", false);
  	header("Pragma: no-cache");
  	error_reporting(E_ALL);
	date_default_timezone_set('America/Lima');

	// Parametros de Configuracion BD
	$db_server	= '127.0.0.1';
	$db_user	= 'gts';
	$db_pass	= 'gts_pass';
	$db_name	= 'gts';
	$db_port	= 3306;

	$conexion 		= @new mysqli($db_server, $db_user, $db_pass, $db_name, $db_port);

	if ($conexion->connect_error){
		die('Error de conectando a la base de datos: ' . $conexion->connect_error);
	}

	$QS_url		= "https://www.appyura.com.pe/geocementos-api/api/locator";
	
	/* 	Token para Expert GPS*/
	$QS_token 	= "TG9jYXRvcjoyMTA1MjAxMTU1MTA6MjA2MDIyMDY1MTQ="; 
	
	$reponseData  	= array();
	$htmlTracks		= "";
	$devicesCount	= 0;

	$sqlQuery 	= "SELECT `id`, `placa`, `longitud`, `latitud`, `velocidad`, date_format(from_unixtime(`fecha`+18000), '%Y-%m-%dT%H:%i:%s') AS 'fecha', `direccion`, `rumbo`, `evento` FROM Yura WHERE `estado`='Nuevo' ORDER BY id DESC LIMIT 50;";

	$resultado 	= $conexion->query($sqlQuery);

	$firstRowID	= 0;
	$lastRowID	= 0;
	$company	= "";

	if ($resultado->num_rows > 0){

		while($row = $resultado->fetch_array(MYSQLI_ASSOC)){
			
			if ($firstRowID == 0){ $firstRowID = $row['id'];}

			$devicesCount++;

			$licplat =	utf8_encode($row['placa']);

			$licplat = 	str_replace("-","",$licplat);

			$reponseData[] = array(
				'placa'          	=> $licplat,
				'latitud'       	=> utf8_encode($row['latitud']),
				'longitud'    		=> utf8_encode($row['longitud']),
				'velocidad'			=> utf8_encode($row['velocidad']),
				'fechaGPS'      	=> utf8_encode($row['fecha']),
				'direccion' 		=> utf8_encode($row['direccion']),
				'rumbo' 			=> utf8_encode($row['rumbo']),
				'evento'    		=> utf8_encode($row['evento'])  
			);

			$htmlTracks   	.=	"
			<tr>
      			<td>".utf8_encode($row['id'])."</td>
      			<td>".$licplat."</td>
        		<td>".utf8_encode($row['fecha'])."</td>
        		<td align='right'>".utf8_encode($row['latitud']).",".utf8_encode($row['longitud'])."</td>
        		<td align='center'>".utf8_encode($row['rumbo'])."</td>
				<td align='center'>".utf8_encode($row['velocidad'])."</td>
				<td align='center'>".utf8_encode($row['evento'])."</td>
				<td align='center'>".$company."</td>
			</tr>";

			$lastRowID = $row['id'];

    	}

	}else{
		die("Todos los registros han sido enviados! No hay data nueva que enviar...");
	}

	$mensajeUpdate	= "";
	  	
	$sqlUpdate 		= "UPDATE Yura SET estado='Sent' WHERE estado='Nuevo' AND id BETWEEN ".$lastRowID." AND ".$firstRowID.";";
	
	if ($conexion->query($sqlUpdate) === TRUE) {
		//print_r($sqlUpdate);
	    $mensajeUpdate	= "Tablas actualizadas!  ";
	} else {
		$mensajeUpdate	= "Error actualizando la tabla ".$conexion->error;
	}
	
	mysqli_close($conexion);

	$curl = curl_init();

	$payload = json_encode($reponseData);

	curl_setopt_array($curl, array(
		CURLOPT_URL 			=> $QS_url,
		CURLOPT_POSTFIELDS 		=> $payload,
		CURLOPT_RETURNTRANSFER 	=> true,
		CURLOPT_ENCODING 		=> "",
		CURLOPT_MAXREDIRS 		=> 10,
		CURLOPT_TIMEOUT 		=> 30,
		CURLOPT_HTTP_VERSION 	=> CURL_HTTP_VERSION_1_1,
		CURLOPT_SSL_VERIFYPEER  => false,
		CURLOPT_CUSTOMREQUEST 	=> "POST",
		CURLOPT_HTTPHEADER 		=> array(
			'Content-Type:application/json',
			"Api-Key: ".$QS_token,
			"cache-control: no-cache"
		),
	));

	$response 	= curl_exec($curl);
	$err 		= curl_error($curl);

	curl_close($curl);

	if ($err) {
		die("cURL Error #:" . $err);
	}

	//die("fin prueba");

	print_r("  <!DOCTYPE html>\n");
	print_r("  <html lang=\"en\">\n");
	print_r("    <head>\n");
	print_r("      <meta charset=\"utf-8\">\n");
	print_r("      <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, shrink-to-fit=no\">\n");
  	print_r("      <link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css\" integrity=\"sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u\" crossorigin=\"anonymous\">");
	print_r("      <title>Rest Client WebService</title>\n");
	print_r("    </head>\n");
	print_r("    <body>\n");
	print_r("      <div class=\"container\">\n");
	print_r("         <nav class=\"navbar navbar-default\">");
	print_r("           <div class=\"container-fluid\">");
	print_r("             <div class=\"navbar-header\">");
	print_r("               <a class=\"navbar-brand\" href=\"#\">");
	print_r("                 WebService ".$QS_url." -> ".$QS_token.".\n");
	print_r("               </a>");
	print_r("             </div>");
	print_r("           </div>");
	print_r("         </nav>");
	print_r("         <div class=\"panel panel-default\">");
	print_r("           <div class=\"panel-body\">");
	print_r("             <table class='table responsive' align='center' cellspacing=1 border=2 cellpadding=5>");
	print_r("               <caption>Registros enviados al webservice: ".$devicesCount." </caption>");
	print_r("               <thead>");
	print_r("                 <tr>");
	print_r("                   <th>ID</th>");
	print_r("                   <th>Patente</th>");
	print_r("                   <th>Fecha</th>");
	print_r("                   <th>Posicion</th>");
	print_r("                   <th align='center'>Rumbo</th>");
	print_r("                   <th align='center'>Velocidad</th>");
	print_r("                   <th align='center'>Evento</th>");
	print_r("                   <th align='center'>Empresa</th>");
	print_r("                 </tr>");
	print_r("               </thead>");
	print_r("               <tbody>");
	print_r($htmlTracks);
	print_r("               </tbody>");
	print_r("             </table>");
	print_r("           </div>");
	print_r("         </div>");
	print_r("         <div class=\"panel panel-default\">");
	print_r("           <div class=\"panel-body\">");
	print_r("             <hr>");
	//print_r(var_dump($response));
	print_r("							<pre><code>".json_encode($reponseData, JSON_PRETTY_PRINT)."</code></pre>");
	print_r("           </div>");
	print_r("         </div>");
	print_r("         <div class=\"panel panel-default\">");
	print_r("           <div class=\"panel-body\">");
	print_r("             <hr>");
	//print_r(var_dump($response));
	print_r("							<pre><code>".json_encode($response, JSON_PRETTY_PRINT)."</code></pre>");
	print_r("           </div>");
	print_r("         </div>");
	print_r("         <div class='mastfoot' align='center'>");
	print_r("           <div class='inner'>");
	print_r("             <p>Sistema desarrollado por  <a href='http://aguilacontrol.com'>AguilaControl</a>, by <a target='_blank' href='https://twitter.com/renato_beltran'>@renato_beltran</a>.</p>");
	print_r("			<p>ID Inicio: ".$lastRowID.", Final: ".$firstRowID." -> ".$mensajeUpdate."</p>");
	print_r("           </div>");
	print_r("         </div>");
	print_r("      </div>\n");
	print_r("    </body>\n");
	print_r("  </html>\n");

	


?>