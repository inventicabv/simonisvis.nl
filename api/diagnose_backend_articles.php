<?php
/**
 * Uitgebreide diagnose voor artikelen die niet zichtbaar zijn in de backend
 * 
 * Dit script controleert:
 * 1. Welke tabelnaam Joomla gebruikt voor content
 * 2. Of artikelen in ENGINE_content staan
 * 3. Asset entries en relaties
 * 4. Published status
 * 5. Access levels
 * 6. Category relaties
 * 7. Mogelijke ACL problemen
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
$expectedTable = $prefix . 'content';

echo "=== UITGEBREIDE DIAGNOSE: Artikelen niet zichtbaar in backend ===\n\n";
echo "Database prefix: {$prefix}\n";
echo "Verwachte tabelnaam: {$expectedTable}\n";
echo "Huidige tabelnaam: ENGINE_content\n\n";

// 1. Controleer welke tabellen bestaan
echo "1. CONTROLEREN TABELLEN...\n";
echo "   ============================================\n";
$tables = $db->getTableList();
$expectedTableExists = false;
$engineTableExists = false;

foreach ($tables as $table) {
    if (strtolower($table) === strtolower($expectedTable) || 
        strtolower($table) === strtolower($prefix . 'content')) {
        $expectedTableExists = true;
        echo "   ✓ Tabel {$table} bestaat\n";
    }
    if (strtolower($table) === 'engine_content') {
        $engineTableExists = true;
        echo "   ✓ Tabel ENGINE_content bestaat\n";
    }
}

if (!$expectedTableExists) {
    echo "   ✗ Tabel {$expectedTable} bestaat NIET\n";
}
if (!$engineTableExists) {
    echo "   ✗ Tabel ENGINE_content bestaat NIET\n";
    echo "   STOP: Tabel ENGINE_content niet gevonden!\n";
    exit;
}
echo "\n";

// 2. Tel artikelen in beide tabellen
echo "2. TELLEN ARTIKELEN...\n";
echo "   ============================================\n";

// Tel in ENGINE_content
$query = $db->getQuery(true)
    ->select('COUNT(*) as total')
    ->from($db->quoteName('ENGINE_content'));
$db->setQuery($query);
$countEngine = $db->loadResult();
echo "   Artikelen in ENGINE_content: {$countEngine}\n";

// Tel in verwachte tabel (als die bestaat)
if ($expectedTableExists) {
    $query = $db->getQuery(true)
        ->select('COUNT(*) as total')
        ->from($db->quoteName($expectedTable));
    $db->setQuery($query);
    $countExpected = $db->loadResult();
    echo "   Artikelen in {$expectedTable}: {$countExpected}\n";
    
    if ($countExpected > 0 && $countEngine > 0) {
        echo "   ⚠ WAARSCHUWING: Artikelen staan in BEIDE tabellen!\n";
        echo "   Joomla gebruikt waarschijnlijk {$expectedTable}, niet ENGINE_content!\n";
    }
} else {
    echo "   ℹ Tabel {$expectedTable} bestaat niet, ENGINE_content is waarschijnlijk de juiste tabel\n";
}
echo "\n";

if ($countEngine == 0) {
    echo "   STOP: Geen artikelen gevonden in ENGINE_content!\n";
    exit;
}

// 3. Controleer published status
echo "3. CONTROLEREN PUBLISHED STATUS...\n";
echo "   ============================================\n";
$query = $db->getQuery(true)
    ->select('state, COUNT(*) as count')
    ->from($db->quoteName('ENGINE_content'))
    ->group('state');
$db->setQuery($query);
$states = $db->loadObjectList();

echo "   Status verdeling:\n";
foreach ($states as $state) {
    $statusName = [
        '-2' => 'Trashed',
        '0' => 'Unpublished',
        '1' => 'Published',
        '2' => 'Archived'
    ];
    $name = $statusName[$state->state] ?? "Unknown ({$state->state})";
    echo "   - {$name}: {$state->count} artikelen\n";
}

$publishedCount = 0;
foreach ($states as $state) {
    if ($state->state == 1) {
        $publishedCount = $state->count;
    }
}

if ($publishedCount == 0) {
    echo "   ⚠ WAARSCHUWING: Geen gepubliceerde artikelen gevonden!\n";
    echo "   Artikelen moeten state = 1 hebben om zichtbaar te zijn.\n";
} else {
    echo "   ✓ {$publishedCount} gepubliceerde artikelen gevonden\n";
}
echo "\n";

// 4. Controleer asset entries
echo "4. CONTROLEREN ASSET ENTRIES...\n";
echo "   ============================================\n";

// Haal parent asset op
$query = $db->getQuery(true)
    ->select('id')
    ->from($db->quoteName($prefix . 'assets'))
    ->where($db->quoteName('name') . ' = ' . $db->quote('com_content'));
$db->setQuery($query);
$parentAssetId = $db->loadResult();

if (!$parentAssetId) {
    echo "   ✗ FOUT: Parent asset 'com_content' niet gevonden!\n";
} else {
    echo "   ✓ Parent asset ID: {$parentAssetId}\n";
}

// Tel asset entries voor artikelen
$query = $db->getQuery(true)
    ->select('COUNT(*) as total')
    ->from($db->quoteName($prefix . 'assets'))
    ->where($db->quoteName('name') . ' LIKE ' . $db->quote('com_content.article.%'));
$db->setQuery($query);
$assetCount = $db->loadResult();
echo "   Asset entries voor artikelen: {$assetCount}\n";

// Vind artikelen zonder asset entry
$query = $db->getQuery(true)
    ->select('COUNT(*) as total')
    ->from($db->quoteName('ENGINE_content', 'c'))
    ->leftJoin($db->quoteName($prefix . 'assets', 'a') . ' ON ' . 
               $db->quoteName('a.id') . ' = ' . $db->quoteName('c.asset_id'))
    ->where($db->quoteName('a.id') . ' IS NULL');
$db->setQuery($query);
$articlesWithoutAsset = $db->loadResult();

if ($articlesWithoutAsset > 0) {
    echo "   ⚠ WAARSCHUWING: {$articlesWithoutAsset} artikelen hebben GEEN asset entry!\n";
    echo "   Dit is waarschijnlijk de hoofdoorzaak!\n";
} else {
    echo "   ✓ Alle artikelen hebben asset entries\n";
}
echo "\n";

// 5. Controleer access levels
echo "5. CONTROLEREN ACCESS LEVELS...\n";
echo "   ============================================\n";
$query = $db->getQuery(true)
    ->select('access, COUNT(*) as count')
    ->from($db->quoteName('ENGINE_content'))
    ->group('access');
$db->setQuery($query);
$accessLevels = $db->loadObjectList();

echo "   Access level verdeling:\n";
foreach ($accessLevels as $level) {
    // Haal access level naam op
    $query = $db->getQuery(true)
        ->select('title')
        ->from($db->quoteName($prefix . 'viewlevels'))
        ->where($db->quoteName('id') . ' = ' . (int)$level->access);
    $db->setQuery($query);
    $levelName = $db->loadResult() ?: "Unknown (ID: {$level->access})";
    echo "   - {$levelName}: {$level->count} artikelen\n";
}
echo "\n";

// 6. Controleer category relaties
echo "6. CONTROLEREN CATEGORY RELATIES...\n";
echo "   ============================================\n";
$query = $db->getQuery(true)
    ->select('c.catid, COUNT(*) as count')
    ->from($db->quoteName('ENGINE_content', 'c'))
    ->group('c.catid');
$db->setQuery($query);
$categories = $db->loadObjectList();

$articlesWithoutCategory = 0;
foreach ($categories as $cat) {
    if ($cat->catid == 0 || $cat->catid == null) {
        $articlesWithoutCategory = $cat->count;
    } else {
        // Controleer of categorie bestaat
        $query = $db->getQuery(true)
            ->select('title, published')
            ->from($db->quoteName($prefix . 'categories'))
            ->where($db->quoteName('id') . ' = ' . (int)$cat->catid);
        $db->setQuery($query);
        $category = $db->loadObject();
        
        if (!$category) {
            echo "   ⚠ Categorie ID {$cat->catid} bestaat niet! ({$cat->count} artikelen)\n";
        } elseif ($category->published != 1) {
            echo "   ⚠ Categorie ID {$cat->catid} ({$category->title}) is niet gepubliceerd! ({$cat->count} artikelen)\n";
        }
    }
}

if ($articlesWithoutCategory > 0) {
    echo "   ⚠ {$articlesWithoutCategory} artikelen hebben geen categorie (catid = 0)\n";
}
echo "\n";

// 7. Controleer content_types
echo "7. CONTROLEREN CONTENT TYPES...\n";
echo "   ============================================\n";
$query = $db->getQuery(true)
    ->select('type_id, type_title, type_alias')
    ->from($db->quoteName($prefix . 'content_types'))
    ->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_content.article'));
$db->setQuery($query);
$contentType = $db->loadObject();

if ($contentType) {
    echo "   ✓ Content type gevonden:\n";
    echo "     - Type ID: {$contentType->type_id}\n";
    echo "     - Type Title: {$contentType->type_title}\n";
    echo "     - Type Alias: {$contentType->type_alias}\n";
} else {
    echo "   ⚠ WAARSCHUWING: Geen content type gevonden voor 'com_content.article'!\n";
}
echo "\n";

// 8. SAMENVATTING EN AANBEVELINGEN
echo "8. SAMENVATTING EN AANBEVELINGEN\n";
echo "   ============================================\n\n";

$problems = [];

if ($expectedTableExists && $countExpected > 0 && $countEngine > 0) {
    $problems[] = "Artikelen staan in BEIDE tabellen. Joomla gebruikt waarschijnlijk {$expectedTable}, niet ENGINE_content.";
}

if ($articlesWithoutAsset > 0) {
    $problems[] = "{$articlesWithoutAsset} artikelen hebben geen asset entry. Dit is waarschijnlijk de hoofdoorzaak!";
}

if ($publishedCount == 0) {
    $problems[] = "Geen gepubliceerde artikelen gevonden (state = 1).";
}

if (count($problems) > 0) {
    echo "   PROBLEMEN GEVONDEN:\n";
    foreach ($problems as $i => $problem) {
        echo "   " . ($i + 1) . ". {$problem}\n";
    }
    echo "\n";
    
    echo "   OPLOSSINGEN:\n";
    if ($articlesWithoutAsset > 0) {
        echo "   1. Voer 'fix_content_assets.php' uit om asset entries aan te maken\n";
    }
    if ($expectedTableExists && $countExpected == 0 && $countEngine > 0) {
        echo "   2. Overweeg om artikelen te migreren van ENGINE_content naar {$expectedTable}\n";
        echo "      OF configureer Joomla om ENGINE_content te gebruiken\n";
    }
    if ($publishedCount == 0) {
        echo "   3. Publiceer artikelen door state = 1 te zetten\n";
    }
} else {
    echo "   ✓ Geen duidelijke problemen gevonden met assets, status of relaties.\n";
    echo "\n";
    echo "   MOGELIJKE OORZAKEN:\n";
    echo "   1. Joomla gebruikt een andere tabelnaam dan ENGINE_content\n";
    echo "   2. Er zijn ACL (permissions) problemen\n";
    echo "   3. Er zijn filters actief in de backend view\n";
    echo "   4. De content component is niet correct geconfigureerd\n";
    echo "\n";
    echo "   VOLGENDE STAPPEN:\n";
    echo "   1. Controleer welke tabelnaam Joomla daadwerkelijk gebruikt\n";
    echo "   2. Controleer de administrator logs voor foutmeldingen\n";
    echo "   3. Controleer of de content component correct is geïnstalleerd\n";
}

echo "\n";

