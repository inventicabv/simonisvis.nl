<?php
/**
 * Script om te controleren of de tabelnaam correct is
 * 
 * Joomla gebruikt meestal #__content (met database prefix)
 * Dit script controleert of ENGINE_content de juiste tabel is
 * of dat artikelen mogelijk in een andere tabel moeten staan
 */

// Laad Joomla framework
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

// Haal database connectie op
$db = Factory::getContainer()->get(DatabaseInterface::class);
$prefix = $db->getPrefix();
$expectedTable = $prefix . 'content';

echo "=== Controle tabelnaam ===\n\n";
echo "Database prefix: {$prefix}\n";
echo "Verwachte tabelnaam: {$expectedTable}\n";
echo "Huidige tabelnaam: ENGINE_content\n\n";

// Controleer of de verwachte tabel bestaat
echo "1. Controleren of {$expectedTable} bestaat...\n";
$tables = $db->getTableList();
$expectedTableExists = in_array($db->replacePrefix($expectedTable), $tables) || 
                       in_array($expectedTable, $tables) ||
                       in_array($prefix . 'content', $tables);

if ($expectedTableExists) {
    echo "   ✓ Tabel {$expectedTable} bestaat\n";
    
    // Tel artikelen in beide tabellen
    $query = $db->getQuery(true)
        ->select('COUNT(*) as total')
        ->from($db->quoteName($expectedTable));
    $db->setQuery($query);
    $countExpected = $db->loadResult();
    echo "   Aantal artikelen in {$expectedTable}: {$countExpected}\n";
} else {
    echo "   ✗ Tabel {$expectedTable} bestaat NIET\n";
}

echo "\n";

// Controleer ENGINE_content
echo "2. Controleren ENGINE_content tabel...\n";
$query = $db->getQuery(true)
    ->select('COUNT(*) as total')
    ->from($db->quoteName('ENGINE_content'));
try {
    $db->setQuery($query);
    $countEngine = $db->loadResult();
    echo "   ✓ Tabel ENGINE_content bestaat\n";
    echo "   Aantal artikelen in ENGINE_content: {$countEngine}\n";
} catch (Exception $e) {
    echo "   ✗ Tabel ENGINE_content bestaat NIET of is niet toegankelijk\n";
    echo "   Fout: " . $e->getMessage() . "\n";
    exit;
}

echo "\n";

// Vergelijk structuur
echo "3. Vergelijken tabelstructuur...\n";
try {
    // Haal kolommen op van beide tabellen
    $query = "SHOW COLUMNS FROM " . $db->quoteName($expectedTable);
    $db->setQuery($query);
    $expectedColumns = $db->loadColumn();
    
    $query = "SHOW COLUMNS FROM " . $db->quoteName('ENGINE_content');
    $db->setQuery($query);
    $engineColumns = $db->loadColumn();
    
    echo "   Kolommen in {$expectedTable}: " . count($expectedColumns) . "\n";
    echo "   Kolommen in ENGINE_content: " . count($engineColumns) . "\n";
    
    $missingInEngine = array_diff($expectedColumns, $engineColumns);
    $extraInEngine = array_diff($engineColumns, $expectedColumns);
    
    if (count($missingInEngine) > 0) {
        echo "   ⚠ Kolommen in {$expectedTable} maar NIET in ENGINE_content:\n";
        foreach ($missingInEngine as $col) {
            echo "      - {$col}\n";
        }
    }
    
    if (count($extraInEngine) > 0) {
        echo "   ⚠ Kolommen in ENGINE_content maar NIET in {$expectedTable}:\n";
        foreach ($extraInEngine as $col) {
            echo "      - {$col}\n";
        }
    }
    
    if (count($missingInEngine) == 0 && count($extraInEngine) == 0) {
        echo "   ✓ Tabellen hebben dezelfde structuur\n";
    }
    
} catch (Exception $e) {
    echo "   ⚠ Kon structuur niet vergelijken: " . $e->getMessage() . "\n";
}

echo "\n";

// Aanbevelingen
echo "4. Aanbevelingen:\n";
echo "   ============================================\n";

if ($expectedTableExists && $countExpected > 0) {
    echo "   ⚠ Er zijn artikelen in BEIDE tabellen!\n";
    echo "   Dit kan verwarring veroorzaken.\n";
    echo "   Joomla gebruikt standaard: {$expectedTable}\n";
    echo "   Overweeg om artikelen te migreren van ENGINE_content naar {$expectedTable}\n";
} elseif (!$expectedTableExists) {
    echo "   ✓ ENGINE_content lijkt de juiste tabel te zijn\n";
    echo "   Maar controleer of Joomla geconfigureerd is om deze tabel te gebruiken\n";
} else {
    echo "   ✓ ENGINE_content bevat artikelen, {$expectedTable} is leeg\n";
    echo "   Dit suggereert dat ENGINE_content de actieve tabel is\n";
    echo "   Zorg ervoor dat:\n";
    echo "   1. Asset entries bestaan (gebruik fix_content_assets.php)\n";
    echo "   2. Content types correct zijn ingesteld\n";
    echo "   3. Published status correct is\n";
}

echo "\n";

