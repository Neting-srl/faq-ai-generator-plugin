<?php
require_once 'vendor/autoload.php';

use Gettext\Translations;
// use Gettext\Scanner\PhpScanner; // Non utilizzata
use Gettext\Generator\PoGenerator;

// Crea una nuova istanza di Translations
$translations = Translations::create('faq-ai-generator');

// Imposta le intestazioni del file POT
// Carica la versione del plugin
require_once 'faq-ai-generator.php';

$translations->setHeader('Project-Id-Version', 'FAQ AI Generator ' . FAQ_AI_GENERATOR_VERSION);
$translations->setHeader('Report-Msgid-Bugs-To', 'https://wordpress.org/support/plugin/faq-ai-generator');
$translations->setHeader('POT-Creation-Date', date('Y-m-d H:i:sO'));
$translations->setHeader('MIME-Version', '1.0');
$translations->setHeader('Content-Type', 'text/plain; charset=UTF-8');
$translations->setHeader('Content-Transfer-Encoding', '8bit');
$translations->setHeader('X-Generator', 'Gettext PHP');

// Cartelle da analizzare
$directories = [
	'.',
	'./admin',
	'./includes',
	'./public'
];

// Aggiungiamo alcune stringhe di esempio per test
$translations->insert('', 'FAQ AI Generator');
$translations->insert('', 'Automatically generate relevant FAQs for your articles, pages, and custom post types using OpenAI\'s AI.');
$translations->insert('', 'FAQ AI Generator is a WordPress plugin that uses OpenAI\'s artificial intelligence to automatically generate relevant questions and answers (FAQs) based on your articles, pages, and custom post types content.');
$translations->insert('', 'Key Features:');
$translations->insert('', 'Automatic FAQ generation using OpenAI GPT');

// Estrai le stringhe traducibili
foreach ($directories as $dir) {
	// Salta la cartella languages
	if ($dir === './languages') {
		continue;
	}

	// Scansiona i file PHP ricorsivamente
	$files = glob($dir . '/**/*.php');
	foreach ($files as $file) {
		// Salta le cartelle vendor e node_modules se presenti
		if (strpos($file, 'vendor/') !== false || strpos($file, 'node_modules/') !== false) {
			continue;
		}

		echo "Analisi del file: $file\n";
		flush();

		// Leggi il contenuto del file
		$source = file_get_contents($file);

		// Estrai le stringhe traducibili (__(), _e(), etc.)
		$phpFunctions = [
			'__' => 'gettext',
			'_e' => 'gettext',
			'_n' => 'ngettext',
			'_x' => 'pgettext',
			'_ex' => 'pgettext',
			'_nx' => 'npgettext',
			'esc_attr__' => 'gettext',
			'esc_attr_e' => 'gettext',
			'esc_html__' => 'gettext',
			'esc_html_e' => 'gettext',
		];

		foreach ($phpFunctions as $function => $gettextFunction) {
			preg_match_all('/' . $function . '\s*\(\s*[\'"](.*?)[\'"]\s*(?:,\s*[\'"](.*?)[\'"]\s*)?(?:,\s*[\'"](.*?)[\'"]\s*)?/s', $source, $matches, PREG_SET_ORDER);

			foreach ($matches as $match) {
				$original = $match[1];
				echo "  Trovata stringa: $original\n";

				if ($gettextFunction === 'gettext') {
					$translations->insert('', $original);
				} elseif ($gettextFunction === 'pgettext' && isset($match[2])) {
					$translations->insert($match[2], $original);
				} elseif ($gettextFunction === 'ngettext' && isset($match[2])) {
					$translations->insert('', $original, $match[2]);
				} elseif ($gettextFunction === 'npgettext' && isset($match[2]) && isset($match[3])) {
					$translations->insert($match[3], $original, $match[2]);
				}

				// Aggiungi il riferimento al file
				$translation = $translations->find('', $original);
				if ($translation) {
					$translation->addReference($file);
				}
			}
		}
	}
}

// Debug
var_dump($translations);

// Stampa delle stringhe trovate
echo "Totale stringhe trovate: " . count($translations) . "\n";

// Genera il file POT
$generator = new PoGenerator();
$potContent = $generator->generateString($translations);
file_put_contents('languages/faq-ai-generator.pot', $potContent);

echo "File POT generato con successo: languages/faq-ai-generator.pot\n";
echo "Dimensione del file: " . filesize('languages/faq-ai-generator.pot') . " bytes\n";