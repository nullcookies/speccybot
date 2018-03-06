<?php

namespace Longman\TelegramBot\Commands\UserCommands;
use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
/**
 * Start command
 *
 * Gets executed when a user first starts using the bot.
 */
class zxinfoCommand extends UserCommand
{

	protected $name = 'zxinfo';
	protected $description = 'Busca en ZXInfo. Devuelve una lista de coincidencias.';
	protected $usage = '/zxinfo <búsqueda>';
	protected $version = '1.0';

	/**
	 * Source of information
	 */
	private $source = "http://zxinfo.dk";

	/**
	 * API entry point
	 */
	private $api_url = "http://api.zxinfo.dk/api/zxinfo/v2/search?";

	/**
	 * Repository URL
	 */
	private $archive_url = "http://www.worldofspectrum.org";

	/**
	 * Frontend Search
	 */
	private $search_url = "http://zxinfo.dk/search/";

	/**
	 * Frontend details
	 */
	private $details_url = "https://spectrumcomputing.co.uk/index.php?cat=96&id=";
	//private $details_url = "http://zxinfo.dk/details/";
	


	public function execute()
	{
		// Some BOT variables
		$message = $this->getMessage();
		$chat_id = $message->getChat()->getId();
		$command_str = trim($message->getText(true));

		$working_msg = Request::sendMessage([ "chat_id" => $chat_id, "text" => "Buscando en ZXInfo..." ]);

		// Commands or no commands?
		switch ($command_str) {
			case "":
				//Hint
				$response = "Usa *{$this->usage}*";
			break;

			// case "*":
			// case "sorprendeme":
			// case "sorpréndeme":
			// case "quejugar":			

			// $response = "*Un juego aleatorio, cortesía de* [ZXInfo]({$this->source}):\n".$this->searchOnZXinfo(false);

			// break;
			case "source":
				$response = "> ".$this->source;
			break;
			default:

			$response = $this->searchOnZXinfo($command_str);
			
		}

		// Return on markdown format		
		$data = [
			'chat_id'    => $chat_id,
			'message_id' => $working_msg->result->message_id,
			'text'       => $response,
			'disable_web_page_preview' => true,
			'parse_mode' => 'markdown'
		];
		return Request::editMessageText($data);
	}


	/**
	 * Fetch the jSON and parse it
	 * @param $q searh query
	 * @return string
	 */
	private function searchOnZXinfo($q = false) {

		$outputlines = OUTPUTLINES * 2;

		if ( $q ) {
			// Lets get ready with the query
			$options = array(
				'listtype' => "list",
				'offset'   => "0",
				'size'     => $outputlines,
				'query'    => urlencode($q)
			);
			$query = http_build_query($options);
		}

		$fetch_url = $this->api_url . $query;
		
		// Fetch the data
		$json = file_get_contents($fetch_url);
		$data = json_decode($json, TRUE);

		// How many we have?
		$hits_total = $data['hits']['total'];

		// Nothing found, inform and exit
		if ( $hits_total == 0 ) {
			$markdown = "No se encontró nada sobre *{$q}* en ZXInfo. Prueba /wos {$q}";
			return $markdown;
		}

		foreach ( $data['hits']['hits'] as $hit ) {
			$details_url = $this->details_url.$hit['_id'];
			$source = $hit['_source'];
			$title = $source['fulltitle'];

			$publisher = $source['publisher'][0]['name'];
			$publisher = ($publisher) ? " {$publisher}" : "";
			
			$availability = $source['availability'];
			$availability = ($availability) ? "  _".$availability."_" : "";

			$year = $source['yearofrelease'];
			$year = ($year) ? " (".$year.")" : "";

			$markdown .= "\xF0\x9F\x95\xB9 [{$title}]({$details_url}) -{$publisher}{$year}{$availability}";
			
			if ( !empty($source['releases'][0]['url']) ) {
				$link = $this->archive_url.$source['releases'][0]['url'];
				$markdown .= " - [Bajar]({$link})";
			}

			$markdown .= "\n";
		}

		// How many left?
		$hits_more = $hits_total - $outputlines;
		if ( $hits_more > 0 ) {
			$search_more_url = $this->search_url . urlencode($q);
			$markdown .= "\n[".$hits_more." resultados más en ZXInfo]({$search_more_url})";
		}

		return $markdown;
	}
}