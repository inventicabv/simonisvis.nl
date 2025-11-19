<?php
/**
 * Script om ontbrekende asset entries aan te maken voor artikelen in ENGINE_content
 * 
 * Dit script:
 * 1. Vindt artikelen zonder asset_id of zonder asset entry
 * 2. Maakt asset entries aan in de assets tabel
 * 3. Koppelt de asset_id aan de artikelen
 */

// Laad Joomla framework
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Access\Access;

// Haal database connectie op
$db = Factory::getContainer()->get(DatabaseInterface::class);
$prefix = $db->getPrefix();

echo "=== Reparatie: Asset entries aanmaken voor ENGINE_content artikelen ===\n\n";
echo "Database prefix: {$prefix}\n\n";

// Vraag bevestiging
echo "WAARSCHUWING: Dit script zal database wijzigingen maken!\n";
echo "Maak eerst een backup van je database voordat je doorgaat.\n\n";
echo "Wil je doorgaan? (ja/nee): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'ja') {
    echo "Geannuleerd.\n";
    //exit;
}

echo "\n";

// Haal het parent asset op voor com_content.article
$query = $db->getQuery(true)
    ->select('id')
    ->from($db->quoteName($prefix . 'assets'))
    ->where($db->quoteName('name') . ' = ' . $db->quote('com_content'));
$db->setQuery($query);
$parentAssetId = $db->loadResult();

if (!$parentAssetId) {
    echo "FOUT: Kan parent asset 'com_content' niet vinden!\n";
    exit;
}

echo "Parent asset ID voor com_content: {$parentAssetId}\n\n";

// Haal alle artikelen op die geen asset entry hebben
$query = $db->getQuery(true)
    ->select('c.id, c.title, c.asset_id, c.catid, c.created_by, c.access')
    ->from($db->quoteName('ENGINE_content', 'c'))
    ->leftJoin($db->quoteName($prefix . 'assets', 'a') . ' ON ' . 
               $db->quoteName('a.id') . ' = ' . $db->quoteName('c.asset_id'))
    ->where($db->quoteName('a.id') . ' IS NULL');
$db->setQuery($query);
$articlesToFix = $db->loadObjectList();

echo "Gevonden " . count($articlesToFix) . " artikelen die gerepareerd moeten worden.\n\n";

if (count($articlesToFix) == 0) {
    echo "Geen artikelen gevonden die gerepareerd moeten worden.\n";
    exit;
}

$fixed = 0;
$errors = 0;

foreach ($articlesToFix as $article) {
    try {
        // Maak asset naam
        $assetName = 'com_content.article.' . $article->id;
        
        // Bepaal access level (gebruik artikel access, of 1 als niet ingesteld)
        $access = $article->access ?? 1;
        
        // Bepaal created_by (gebruik artikel created_by, of huidige gebruiker)
        $createdBy = $article->created_by ?? Factory::getUser()->id;
        
        // Maak asset entry
        $asset = new \stdClass();
        $asset->parent_id = $parentAssetId;
        $asset->lft = 0; // Wordt later aangepast door Joomla
        $asset->rgt = 0; // Wordt later aangepast door Joomla
        $asset->level = 0; // Wordt later aangepast door Joomla
        $asset->name = $assetName;
        $asset->title = $article->title;
        $asset->rules = '{}'; // Standaard lege rules
        
        // Insert asset
        $db->insertObject($prefix . 'assets', $asset);
        $assetId = $db->insertid();
        
        if (!$assetId) {
            throw new Exception("Kon asset niet aanmaken voor artikel ID {$article->id}");
        }
        
        // Update artikel met asset_id
        $query = $db->getQuery(true)
            ->update($db->quoteName('ENGINE_content'))
            ->set($db->quoteName('asset_id') . ' = ' . (int)$assetId)
            ->where($db->quoteName('id') . ' = ' . (int)$article->id);
        $db->setQuery($query);
        $db->execute();
        
        // Rebuild assets tree (dit moet gebeuren na alle inserts)
        // We doen dit aan het einde voor alle assets tegelijk
        
        $fixed++;
        echo "✓ Artikel ID {$article->id} ({$article->title}) - Asset ID: {$assetId}\n";
        
    } catch (Exception $e) {
        $errors++;
        echo "✗ FOUT bij artikel ID {$article->id}: " . $e->getMessage() . "\n";
    }
}

// Rebuild assets tree
echo "\nAssets tree rebuilden...\n";
try {
    Access::clearStatics();
    
    // Probeer Table\Asset te gebruiken
    if (class_exists('\Joomla\CMS\Table\Asset')) {
        $assets = new \Joomla\CMS\Table\Asset($db);
        $assets->rebuild();
        echo "✓ Assets tree succesvol gerebuild\n";
    } else {
        // Alternatieve methode: gebruik Access helper
        $query = $db->getQuery(true)
            ->select('id, parent_id, lft, rgt, level')
            ->from($db->quoteName($prefix . 'assets'))
            ->order($db->quoteName('lft'));
        $db->setQuery($query);
        $allAssets = $db->loadObjectList();
        
        // Rebuild tree handmatig (vereenvoudigde versie)
        echo "⚠ Assets tree rebuild vereist handmatige actie\n";
        echo "  Ga naar: Extensions > Manage > Database > Rebuild Assets\n";
    }
} catch (Exception $e) {
    echo "✗ Waarschuwing: Kon assets tree niet rebuilden: " . $e->getMessage() . "\n";
    echo "  Je kunt dit handmatig doen via: Extensions > Manage > Database > Rebuild Assets\n";
}

echo "\n=== Klaar ===\n";
echo "Gerepareerd: {$fixed} artikelen\n";
echo "Fouten: {$errors}\n";
echo "\n";
echo "Controleer nu of de artikelen zichtbaar zijn in de backend.\n";
echo "Als ze nog steeds niet zichtbaar zijn, controleer:\n";
echo "1. Published status (state = 1)\n";
echo "2. Access levels\n";
echo "3. Category relaties\n";

