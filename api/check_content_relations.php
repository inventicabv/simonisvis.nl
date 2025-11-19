<?php
/**
 * Script om te controleren waarom artikelen uit ENGINE_content niet zichtbaar zijn in de backend
 * 
 * Dit script controleert:
 * 1. Of artikelen in ENGINE_content staan
 * 2. Of deze artikelen entries hebben in de assets tabel
 * 3. Of deze artikelen entries hebben in de content_types tabel
 * 4. Of de asset_id correct is gekoppeld
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

echo "=== Diagnose: ENGINE_content artikelen niet zichtbaar in backend ===\n\n";
echo "Database prefix: {$prefix}\n\n";

// 1. Controleer artikelen in ENGINE_content
echo "1. Controleren artikelen in ENGINE_content tabel...\n";
$query = $db->getQuery(true)
    ->select('COUNT(*) as total')
    ->from($db->quoteName('ENGINE_content'));
$db->setQuery($query);
$totalArticles = $db->loadResult();
echo "   Gevonden: {$totalArticles} artikelen\n\n";

if ($totalArticles == 0) {
    echo "GEEN ARTIKELEN GEVONDEN in ENGINE_content!\n";
    exit;
}

// 2. Controleer welke artikelen GEEN asset_id hebben
echo "2. Controleren artikelen zonder asset_id...\n";
$query = $db->getQuery(true)
    ->select('id, title, asset_id')
    ->from($db->quoteName('ENGINE_content'))
    ->where($db->quoteName('asset_id') . ' = 0 OR ' . $db->quoteName('asset_id') . ' IS NULL');
$db->setQuery($query);
$articlesWithoutAsset = $db->loadObjectList();
echo "   Gevonden: " . count($articlesWithoutAsset) . " artikelen zonder asset_id\n";

if (count($articlesWithoutAsset) > 0) {
    echo "   Eerste 5 artikelen zonder asset_id:\n";
    foreach (array_slice($articlesWithoutAsset, 0, 5) as $article) {
        echo "   - ID: {$article->id}, Titel: {$article->title}, Asset ID: " . ($article->asset_id ?? 'NULL') . "\n";
    }
}
echo "\n";

// 3. Controleer of assets bestaan voor artikelen
echo "3. Controleren assets tabel entries...\n";
$query = $db->getQuery(true)
    ->select('COUNT(*) as total')
    ->from($db->quoteName($prefix . 'assets'))
    ->where($db->quoteName('name') . ' LIKE ' . $db->quote('com_content.article.%'));
$db->setQuery($query);
$totalAssets = $db->loadResult();
echo "   Gevonden: {$totalAssets} asset entries voor artikelen\n\n";

// 4. Controleer welke artikelen GEEN asset entry hebben
echo "4. Controleren artikelen zonder asset entry...\n";
$query = $db->getQuery(true)
    ->select('c.id, c.title, c.asset_id')
    ->from($db->quoteName('ENGINE_content', 'c'))
    ->leftJoin($db->quoteName($prefix . 'assets', 'a') . ' ON ' . 
               $db->quoteName('a.id') . ' = ' . $db->quoteName('c.asset_id'))
    ->where($db->quoteName('a.id') . ' IS NULL');
$db->setQuery($query);
$articlesWithoutAssetEntry = $db->loadObjectList();
echo "   Gevonden: " . count($articlesWithoutAssetEntry) . " artikelen zonder asset entry\n";

if (count($articlesWithoutAssetEntry) > 0) {
    echo "   Eerste 5 artikelen zonder asset entry:\n";
    foreach (array_slice($articlesWithoutAssetEntry, 0, 5) as $article) {
        echo "   - ID: {$article->id}, Titel: {$article->title}, Asset ID: " . ($article->asset_id ?? 'NULL') . "\n";
    }
}
echo "\n";

// 5. Controleer content_types tabel
echo "5. Controleren content_types tabel...\n";
$query = $db->getQuery(true)
    ->select('type_id, type_title, type_alias')
    ->from($db->quoteName($prefix . 'content_types'))
    ->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_content.article'));
$db->setQuery($query);
$contentType = $db->loadObject();
if ($contentType) {
    echo "   Content type gevonden:\n";
    echo "   - Type ID: {$contentType->type_id}\n";
    echo "   - Type Title: {$contentType->type_title}\n";
    echo "   - Type Alias: {$contentType->type_alias}\n";
} else {
    echo "   WAARSCHUWING: Geen content type gevonden voor 'com_content.article'!\n";
}
echo "\n";

// 6. Controleer of artikelen de juiste typeAlias hebben (als veld bestaat)
echo "6. Samenvatting en aanbevelingen:\n";
echo "   ============================================\n";
echo "   Totaal artikelen in ENGINE_content: {$totalArticles}\n";
echo "   Artikelen zonder asset_id: " . count($articlesWithoutAsset) . "\n";
echo "   Artikelen zonder asset entry: " . count($articlesWithoutAssetEntry) . "\n";
echo "   Asset entries voor artikelen: {$totalAssets}\n";
echo "\n";

if (count($articlesWithoutAssetEntry) > 0 || count($articlesWithoutAsset) > 0) {
    echo "   PROBLEEM GEVONDEN!\n";
    echo "   De artikelen hebben geen correcte relatie met de assets tabel.\n";
    echo "   Dit is nodig voor ACL (Access Control List) en zichtbaarheid in de backend.\n\n";
    echo "   OPLOSSING:\n";
    echo "   Voer het script 'fix_content_assets.php' uit om dit te repareren.\n";
} else {
    echo "   Geen problemen gevonden met assets.\n";
    echo "   Controleer andere mogelijke oorzaken:\n";
    echo "   - Published status van artikelen\n";
    echo "   - Access levels\n";
    echo "   - Category relaties\n";
}

echo "\n";

