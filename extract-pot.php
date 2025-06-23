<?php
/**
 * Script per generare il file POT per il plugin FAQ AI Generator
 */

// Carica la versione del plugin
$plugin_data = file_get_contents('faq-ai-generator.php');
preg_match('/Version:\s+(\d+\.\d+\.\d+)/', $plugin_data, $matches);
$version = isset($matches[1]) ? $matches[1] : '1.0.0';

// Data corrente nel formato POT
$now = date('Y-m-d H:i:sO');

// Intestazione del file POT
$header = "msgid \"\"\n";
$header .= "msgstr \"\"\n";
$header .= "\"Project-Id-Version: FAQ AI Generator $version\\n\"\n";
$header .= "\"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/faq-ai-generator\\n\"\n";
$header .= "\"POT-Creation-Date: $now\\n\"\n";
$header .= "\"MIME-Version: 1.0\\n\"\n";
$header .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
$header .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
$header .= "\"X-Generator: Manual POT Generator\\n\"\n";
$header .= "\"X-Poedit-KeywordsList: __;_e;_n:1,2;_x:1,2c;_ex:1,2c;_nx:4c,1,2;esc_attr__;";
$header .= "esc_attr_e;esc_attr_x:1,2c;esc_html__;esc_html_e;esc_html_x:1,2c;_n_noop:1,2;";
$header .= "_nx_noop:3c,1,2;__ngettext_noop:1,2\\n\"\n";
$header .= "\"X-Poedit-Basepath: ..\\n\"\n";
$header .= "\"X-Poedit-SearchPath-0: .\\n\"\n";
$header .= "\"X-Poedit-SearchPathExcluded-0: .git\\n\"\n";
$header .= "\"X-Poedit-SearchPathExcluded-1: languages\\n\"\n";
$header .= "\"X-Poedit-SearchPathExcluded-2: vendor\\n\"\n";
$header .= "\"X-Poedit-SearchPathExcluded-3: node_modules\\n\"\n\n";

// Funzione per estrarre stringhe traducibili da un file
function extract_strings($file)
{
	$strings = array();
	$content = file_get_contents($file);

	// Funzioni di traduzione WordPress
	$functions = array(
		'__' => 1,            // __('string')
		'_e' => 1,            // _e('string')
		'_n' => 1,            // _n('singular', 'plural', number)
		'_x' => 1,            // _x('string', 'context')
		'_ex' => 1,           // _ex('string', 'context')
		'_nx' => 1,           // _nx('singular', 'plural', number, 'context')
		'esc_attr__' => 1,    // esc_attr__('string')
		'esc_attr_e' => 1,    // esc_attr_e('string')
		'esc_html__' => 1,    // esc_html__('string')
		'esc_html_e' => 1,    // esc_html_e('string')
	);

	foreach ($functions as $function => $param_pos) {
		// La regex cerca il nome della funzione seguito da una stringa tra apici o virgolette
		$regex = '/' . $function . '\s*\(\s*[\'"](.+?)[\'"]\s*(?:[,\)])/';
		preg_match_all($regex, $content, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			// Pulizia della stringa, rimuove gli escape doppi
			$string = str_replace('\\\'', '\'', $match[1]);
			$string = str_replace('\\"', '"', $string);

			// Aggiungi al array solo se non è già presente
			if (!isset($strings[$string])) {
				$strings[$string] = array(
					'string' => $string,
					'files' => array($file)
				);
			} else {
				// Aggiungi il file all'elenco dei riferimenti se è diverso
				if (!in_array($file, $strings[$string]['files'])) {
					$strings[$string]['files'][] = $file;
				}
			}
		}
	}

	return $strings;
}

// Cartelle da analizzare
$directories = array(
	'.',
	'./admin',
	'./includes',
	'./public'
);

// Array per memorizzare tutte le stringhe
$all_strings = array();

echo "Inizio analisi dei file per estrazione stringhe...\n";

// Analizza tutti i file PHP nelle directory specificate
foreach ($directories as $dir) {
	echo "Analisi directory: $dir\n";

	// Scansiona la directory in modo ricorsivo per trovare file PHP
	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir)
	);

	foreach ($it as $file) {
		// Salta elementi che non sono file o non hanno estensione .php
		if (!$file->isFile() || $file->getExtension() !== 'php') {
			continue;
		}

		$filepath = $file->getPathname();

		// Salta file in vendor/ o altre directory da ignorare
		if (
			strpos($filepath, 'vendor/') !== false ||
			strpos($filepath, 'node_modules/') !== false ||
			strpos($filepath, 'languages/') !== false
		) {
			continue;
		}

		echo "  Analisi file: " . $filepath . "\n";

		// Estrai stringhe dal file
		$strings = extract_strings($filepath);

		// Unisci con tutte le stringhe trovate finora
		foreach ($strings as $key => $data) {
			if (!isset($all_strings[$key])) {
				$all_strings[$key] = $data;
			} else {
				foreach ($data['files'] as $ref) {
					if (!in_array($ref, $all_strings[$key]['files'])) {
						$all_strings[$key]['files'][] = $ref;
					}
				}
			}
		}
	}
}

// Inizia con l'intestazione
$pot_content = $header;

// Genera il contenuto del file POT con tutte le stringhe trovate
foreach ($all_strings as $data) {
	$string = $data['string'];
	$files = $data['files'];

	// Aggiungi i riferimenti ai file
	foreach ($files as $file) {
		$pot_content .= "#: " . str_replace('\\', '/', $file) . "\n";
	}

	// Aggiungi la stringa
	$pot_content .= "msgid \"" . addcslashes($string, "\"\r\n") . "\"\n";
	$pot_content .= "msgstr \"\"\n\n";
}

// Se non ci sono stringhe trovate, aggiungi alcune stringhe di esempio per test
if (count($all_strings) == 0) {
	$pot_content .= "#: faq-ai-generator.php:10\n";
	$pot_content .= "msgid \"FAQ AI Generator\"\n";
	$pot_content .= "msgstr \"\"\n\n";

	$pot_content .= "#: faq-ai-generator.php:12\n";
	$pot_content .= "msgid \"Automatically generate relevant FAQs for your articles, pages, and custom post types using OpenAI's AI.\"\n";
	$pot_content .= "msgstr \"\"\n\n";
}

// Scrivi il file POT
$pot_file = 'languages/faq-ai-generator.pot';
file_put_contents($pot_file, $pot_content);

$size = filesize($pot_file);
echo "File POT generato con successo: $pot_file ($size bytes)\n";
echo "Totale stringhe trovate: " . count($all_strings) . "\n";