<?php
/**
 * Script om te controleren welke tabelnaam Joomla daadwerkelijk gebruikt
 * en of artikelen mogelijk gemigreerd moeten worden
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

echo "=== Controleren welke tabel Joomla gebruikt ===\n\n";
echo "Database prefix: {$prefix}\n";
echo "Verwachte tabelnaam: {$expectedTable}\n\n";

// Probeer het content model te laden om te zien welke tabel het gebruikt
echo "1. Controleren Joomla Content Model...\n";
echo "   ============================================\n";

try {
    // Probeer het administrator content model te laden
    $component = Factory::getApplication()->bootComponent('com_content');
    
    if ($component) {
        $model = $component->getMVCFactory()->createModel('Articles', 'Administrator', ['ignore_request' => true]);
        
        if ($model && method_exists($model, 'getListQuery')) {
            // Haal de query op om te zien welke tabel wordt gebruikt
            $query = $model->getListQuery();
            $queryString = (string)$query;
            
            echo "   Query gevonden!\n";
            
            // Zoek naar tabelnamen in de query
            if (preg_match('/FROM\s+[`"]?([a-z_]+content)[`"]?/i', $queryString, $matches)) {
                $usedTable = $matches[1];
                echo "   Tabel gebruikt door model: {$usedTable}\n";
                
                if (strtolower($usedTable) === strtolower($expectedTable) || 
                    strtolower($usedTable) === strtolower($prefix . 'content')) {
                    echo "   ✓ Model gebruikt de verwachte tabel: {$usedTable}\n";
                } elseif (strtolower($usedTable) === 'engine_content') {
                    echo "   ✓ Model gebruikt ENGINE_content\n";
                } else {
                    echo "   ⚠ Model gebruikt onverwachte tabel: {$usedTable}\n";
                }
            } else {
                echo "   ⚠ Kon tabelnaam niet uit query halen\n";
                echo "   Query preview: " . substr($queryString, 0, 200) . "...\n";
            }
        } else {
            echo "   ⚠ Kon getListQuery() niet aanroepen op model\n";
        }
    } else {
        echo "   ⚠ Kon com_content component niet laden\n";
    }
} catch (Exception $e) {
    echo "   ⚠ Fout bij laden model: " . $e->getMessage() . "\n";
}

echo "\n";

// Controleer welke tabellen bestaan en hoeveel artikelen erin staan
echo "2. Vergelijken tabellen...\n";
echo "   ============================================\n";

$tables = $db->getTableList();
$foundTables = [];

foreach ($tables as $table) {
    $tableLower = strtolower($table);
    if (strpos($tableLower, 'content') !== false) {
        $foundTables[] = $table;
        
        // Tel artikelen
        try {
            $query = $db->getQuery(true)
                ->select('COUNT(*) as total')
                ->from($db->quoteName($table));
            $db->setQuery($query);
            $count = $db->loadResult();
            
            echo "   Tabel: {$table} - {$count} artikelen\n";
        } catch (Exception $e) {
            echo "   Tabel: {$table} - Fout bij tellen: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n";

// Controleer of ENGINE_content en verwachte tabel verschillende data hebben
if (in_array($expectedTable, $foundTables) || in_array($prefix . 'content', $foundTables)) {
    $actualExpectedTable = in_array($expectedTable, $foundTables) ? $expectedTable : $prefix . 'content';
    
    echo "3. Vergelijken data tussen tabellen...\n";
    echo "   ============================================\n";
    
    // Tel in beide tabellen
    $query = $db->getQuery(true)
        ->select('COUNT(*) as total')
        ->from($db->quoteName('ENGINE_content'));
    $db->setQuery($query);
    $countEngine = $db->loadResult();
    
    $query = $db->getQuery(true)
        ->select('COUNT(*) as total')
        ->from($db->quoteName($actualExpectedTable));
    $db->setQuery($query);
    $countExpected = $db->loadResult();
    
    echo "   ENGINE_content: {$countEngine} artikelen\n";
    echo "   {$actualExpectedTable}: {$countExpected} artikelen\n\n";
    
    if ($countEngine > 0 && $countExpected == 0) {
        echo "   ⚠ PROBLEEM GEVONDEN!\n";
        echo "   Artikelen staan in ENGINE_content maar NIET in {$actualExpectedTable}\n";
        echo "   Joomla gebruikt waarschijnlijk {$actualExpectedTable}, niet ENGINE_content!\n\n";
        echo "   OPLOSSING:\n";
        echo "   Je hebt twee opties:\n";
        echo "   1. Migreer artikelen van ENGINE_content naar {$actualExpectedTable}\n";
        echo "   2. Configureer Joomla om ENGINE_content te gebruiken (complexer)\n\n";
        echo "   Wil je de artikelen migreren? (ja/nee): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) === 'ja') {
            echo "\n   Migratie starten...\n";
            
            // Haal alle artikelen op uit ENGINE_content
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('ENGINE_content'));
            $db->setQuery($query);
            $articles = $db->loadObjectList();
            
            $migrated = 0;
            $errors = 0;
            
            foreach ($articles as $article) {
                try {
                    // Controleer of artikel al bestaat in doel tabel
                    $query = $db->getQuery(true)
                        ->select('id')
                        ->from($db->quoteName($actualExpectedTable))
                        ->where($db->quoteName('id') . ' = ' . (int)$article->id);
                    $db->setQuery($query);
                    $exists = $db->loadResult();
                    
                    if (!$exists) {
                        // Insert artikel in doel tabel
                        $db->insertObject($actualExpectedTable, $article);
                        $migrated++;
                        echo "   ✓ Artikel ID {$article->id} gemigreerd\n";
                    } else {
                        echo "   - Artikel ID {$article->id} bestaat al in doel tabel, overgeslagen\n";
                    }
                } catch (Exception $e) {
                    $errors++;
                    echo "   ✗ Fout bij migreren artikel ID {$article->id}: " . $e->getMessage() . "\n";
                }
            }
            
            echo "\n   Migratie voltooid:\n";
            echo "   - Gemigreerd: {$migrated}\n";
            echo "   - Fouten: {$errors}\n";
            echo "\n   BELANGRIJK: Controleer nu of artikelen zichtbaar zijn in de backend!\n";
        } else {
            echo "   Migratie geannuleerd.\n";
        }
    } elseif ($countEngine > 0 && $countExpected > 0) {
        echo "   ⚠ Artikelen staan in BEIDE tabellen!\n";
        echo "   Dit kan verwarring veroorzaken.\n";
    } elseif ($countEngine == 0 && $countExpected > 0) {
        echo "   ✓ Artikelen staan in {$actualExpectedTable}, niet in ENGINE_content\n";
        echo "   Dit is correct voor Joomla.\n";
    }
} else {
    echo "3. Tabel {$expectedTable} bestaat niet\n";
    echo "   ENGINE_content is waarschijnlijk de juiste tabel\n";
}

echo "\n";

