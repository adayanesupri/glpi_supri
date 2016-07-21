<?php
######################################
#Coded By Jijo Last Update Date[Jan/19/06]
#####################################
###################### Class readattachment ###############
/*//SUPRISERVICE*/
class readattachment
{
	function getdecodevalue($message,$coding)
	{
		switch($coding)
		{
			case 1:
				$message = imap_8bit($message);
				break;
			case 3:
				$message=imap_base64($message);
				break;
		}
		return $message;
	}

	function getdata( $host, $login, $password, $extensions )
	{
		$mbox = imap_open($host,  $login, $password ) or die("can't connect: " . imap_last_error());

		$message = array();
		$message["attachment"]["type"][0] = "text";
		$message["attachment"]["type"][1] = "multipart";
		$message["attachment"]["type"][2] = "message";
		$message["attachment"]["type"][3] = "application";
		$message["attachment"]["type"][4] = "audio";
		$message["attachment"]["type"][5] = "image";
		$message["attachment"]["type"][6] = "video";
		$message["attachment"]["type"][7] = "other";

		$extensions = explode( ",", $extensions );

		$search = imap_search($mbox, 'UNSEEN');

		$dtIDX = 0;
		$iIDX = 0;
		//função do IMAP estava retornado array desordenado, então foi necessário buscar o email mais antigo na força bruta
		for ($idx = 0; $idx < count($search); $idx++)
		{
			$header = imap_header($mbox, $search[$idx]);
			$older = self::isOlderThan( $header->MailDate, $dtIDX );
			if ( $older > 0 || $dtIDX == 0 )
			{
				
				$dtIDX = $header->MailDate;
				$iIDX = $idx;
			}
		}

		print "Primeiro email: idx {$iIDX}, data {$dtIDX}<br>";

		$idx = $iIDX;
		//for ($idx = 0; $idx < count($search); $idx++)
		{
			//$header = imap_header($mbox, $search[$idx]);
			//continue;
			$structure = imap_fetchstructure($mbox, $search[$idx]);

			$parts = $structure->parts;
			$fpos = 2;
			for( $i = 1; $i < count($parts); $i++ )
			{
				$message["pid"][$i] = ($i);
				$part = $parts[$i];
				//if( strtolower( $part->disposition ) == "attachment" )
				{
					$message["type"][$i] = $message["attachment"]["type"][$part->type] . "/" . strtolower( $part->subtype );
					$message["subtype"][$i] = strtolower( $part->subtype );

					//se a extensão do anexo não estiver na lista dos procurados, pula.
					if ( !in_array( strtoupper( $part->subtype ), array_change_key_case( $extensions, CASE_UPPER ) ) )
						 continue;

					$params = $part->parameters;
					$filename = "../files/_dataimport/";
					if  ( $params[0]->value == "Relatorio - Exportacao de Contadores.csv" )
					{
						$hoje = date("Y-m-d H-i-s");
						$filename .= "facilitymanager_" . $hoje . ".csv";

						$mege = imap_fetchbody( $mbox, $search[$idx], $fpos );
						$data = imap_8bit( $mege );
						$data = $this->getdecodevalue( $mege,$part->encoding );	
						$fp = fopen( $filename, "w" );
						fputs( $fp, $data );
						fclose( $fp );

						return $filename;
					}
					$fpos += 1; 
				}
			}
			imap_setflag_full( $mbox, $search[$idx], '\\Seen \\Flagged' );
		}
		imap_close( $mbox );
		return null;
	}

	function isOlderThan( $a, $b )
	{
		return strtotime($b) - strtotime($a);
	}
}
?>
