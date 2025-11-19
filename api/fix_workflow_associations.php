<?php
/**
 * Script om workflow associations aan te maken voor artikelen die deze missen
 * HTML versie voor browser met formulier
 */

define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

header('Content-Type: text/html; charset=utf-8');

$db = Factory::getContainer()->get(DatabaseInterface::class);
$prefix = $db->getPrefix();
$visibleArticleId = 145;

function h($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

$confirmed = isset($_POST['confirm']) && $_POST['confirm'] === 'ja';
$fixed = 0;
$errors = 0;
$results = [];

if ($confirmed) {
    // Controleer workflow tabellen
    $workflowTables = [
        $prefix . 'workflow_associations',
        $prefix . 'workflows',
        $prefix . 'workflow_stages'
    ];
    
    $allTablesExist = true;
    $tables = $db->getTableList();
    foreach ($workflowTables as $table) {
        $exists = false;
        foreach ($tables as $t) {
            if (strtolower($t) === strtolower($table)) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $allTablesExist = false;
            break;
        }
    }
    
    if ($allTablesExist) {
        // Haal referentie workflow op
        $query = $db->getQuery(true)
            ->select('wa.workflow_id, wa.stage_id')
            ->from($db->quoteName($prefix . 'workflow_associations', 'wa'))
            ->where($db->quoteName('wa.item_id') . ' = ' . (int)$visibleArticleId)
            ->where($db->quoteName('wa.extension') . ' = ' . $db->quote('com_content.article'));
        $db->setQuery($query);
        $referenceWorkflow = $db->loadObject();
        
        if ($referenceWorkflow) {
            // Vind artikelen zonder workflow
            $query = $db->getQuery(true)
                ->select('c.id, c.title')
                ->from($db->quoteName('ENGINE_content', 'c'))
                ->leftJoin($db->quoteName($prefix . 'workflow_associations', 'wa') . ' ON ' . 
                           $db->quoteName('wa.item_id') . ' = ' . $db->quoteName('c.id') . 
                           ' AND ' . $db->quoteName('wa.extension') . ' = ' . $db->quote('com_content.article'))
                ->where($db->quoteName('wa.id') . ' IS NULL');
            $db->setQuery($query);
            $articlesToFix = $db->loadObjectList();
            
            foreach ($articlesToFix as $article) {
                try {
                    // Controleer of al bestaat
                    $query = $db->getQuery(true)
                        ->select('id')
                        ->from($db->quoteName($prefix . 'workflow_associations'))
                        ->where($db->quoteName('item_id') . ' = ' . (int)$article->id)
                        ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content.article'));
                    $db->setQuery($query);
                    $exists = $db->loadResult();
                    
                    if (!$exists) {
                        $association = new \stdClass();
                        $association->item_id = $article->id;
                        $association->stage_id = $referenceWorkflow->stage_id;
                        $association->extension = 'com_content.article';
                        
                        $db->insertObject($prefix . 'workflow_associations', $association);
                        $fixed++;
                        $results[] = ['type' => 'success', 'msg' => 'Artikel ID ' . $article->id . ' (' . $article->title . ') - Workflow association aangemaakt'];
                    } else {
                        $results[] = ['type' => 'info', 'msg' => 'Artikel ID ' . $article->id . ' heeft al een workflow association, overgeslagen'];
                    }
                } catch (Exception $e) {
                    $errors++;
                    $results[] = ['type' => 'error', 'msg' => 'Fout bij artikel ID ' . $article->id . ': ' . $e->getMessage()];
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repareer Workflow Associations</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px 0 0;
        }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        form { margin-top: 20px; }
        .results { margin-top: 20px; max-height: 400px; overflow-y: auto; }
        .result-item { padding: 8px; margin: 5px 0; border-radius: 4px; }
        .result-success { background: #d4edda; }
        .result-error { background: #f8d7da; }
        .result-info { background: #e8f4f8; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Repareer Workflow Associations</h1>
    
    <div class="info">
        <strong>Database prefix:</strong> <?php echo h($prefix); ?><br>
        <strong>Referentie artikel:</strong> ID <?php echo $visibleArticleId; ?>
    </div>

<?php
if (!$confirmed) {
    // Controleer workflow tabellen
    $workflowTables = [
        $prefix . 'workflow_associations',
        $prefix . 'workflows',
        $prefix . 'workflow_stages'
    ];
    
    $allTablesExist = true;
    $tables = $db->getTableList();
    foreach ($workflowTables as $table) {
        $exists = false;
        foreach ($tables as $t) {
            if (strtolower($t) === strtolower($table)) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $allTablesExist = false;
            break;
        }
    }
    
    if (!$allTablesExist) {
        echo '<div class="error"><strong>✗ Fout:</strong> Workflow tabellen bestaan niet! Workflows zijn mogelijk niet actief in deze Joomla installatie.</div>';
    } else {
        // Haal referentie workflow op
        $query = $db->getQuery(true)
            ->select('wa.workflow_id, wa.stage_id, w.title as workflow_title, ws.title as stage_title')
            ->from($db->quoteName($prefix . 'workflow_associations', 'wa'))
            ->leftJoin($db->quoteName($prefix . 'workflows', 'w') . ' ON ' . 
                       $db->quoteName('w.id') . ' = ' . $db->quoteName('wa.workflow_id'))
            ->leftJoin($db->quoteName($prefix . 'workflow_stages', 'ws') . ' ON ' . 
                       $db->quoteName('ws.id') . ' = ' . $db->quoteName('wa.stage_id'))
            ->where($db->quoteName('wa.item_id') . ' = ' . (int)$visibleArticleId)
            ->where($db->quoteName('wa.extension') . ' = ' . $db->quote('com_content.article'));
        $db->setQuery($query);
        $referenceWorkflow = $db->loadObject();
        
        if (!$referenceWorkflow) {
            echo '<div class="error"><strong>✗ Fout:</strong> Kan workflow association niet vinden voor artikel ID ' . $visibleArticleId . '! Dit artikel moet eerst een workflow association hebben.</div>';
        } else {
            // Tel artikelen zonder workflow
            $query = $db->getQuery(true)
                ->select('COUNT(DISTINCT c.id) as total')
                ->from($db->quoteName('ENGINE_content', 'c'))
                ->leftJoin($db->quoteName($prefix . 'workflow_associations', 'wa') . ' ON ' . 
                           $db->quoteName('wa.item_id') . ' = ' . $db->quoteName('c.id') . 
                           ' AND ' . $db->quoteName('wa.extension') . ' = ' . $db->quote('com_content.article'))
                ->where($db->quoteName('wa.id') . ' IS NULL');
            $db->setQuery($query);
            $articlesWithoutWorkflow = $db->loadResult();
            
            echo '<div class="warning">';
            echo '<strong>⚠ WAARSCHUWING:</strong> Dit script zal database wijzigingen maken!<br>';
            echo 'Maak eerst een backup van je database voordat je doorgaat.';
            echo '</div>';
            
            echo '<div class="info">';
            echo '<strong>Referentie workflow (van artikel ID ' . $visibleArticleId . '):</strong><br>';
            echo 'Workflow ID: ' . h($referenceWorkflow->workflow_id) . ' (' . h($referenceWorkflow->workflow_title) . ')<br>';
            echo 'Stage ID: ' . h($referenceWorkflow->stage_id) . ' (' . h($referenceWorkflow->stage_title) . ')';
            echo '</div>';
            
            if ($articlesWithoutWorkflow > 0) {
                echo '<div class="warning">';
                echo '<strong>Gevonden:</strong> ' . $articlesWithoutWorkflow . ' artikelen zonder workflow association.<br>';
                echo 'Deze zullen worden gerepareerd met dezelfde workflow/stage als artikel ID ' . $visibleArticleId . '.';
                echo '</div>';
                
                echo '<form method="POST">';
                echo '<input type="hidden" name="confirm" value="ja">';
                echo '<button type="submit" class="btn btn-danger">Ja, repareer workflow associations</button>';
                echo '<a href="compare_visible_article.php" class="btn">Terug naar analyse</a>';
                echo '</form>';
            } else {
                echo '<div class="success">✓ Geen artikelen gevonden die gerepareerd moeten worden. Alle artikelen hebben al een workflow association.</div>';
            }
        }
    }
} else {
    // Toon resultaten
    echo '<h2>Resultaten</h2>';
    
    if (count($results) > 0) {
        echo '<div class="results">';
        foreach ($results as $result) {
            $class = 'result-' . $result['type'];
            echo '<div class="result-item ' . $class . '">' . h($result['msg']) . '</div>';
        }
        echo '</div>';
    }
    
    echo '<div class="success">';
    echo '<strong>Klaar!</strong><br>';
    echo 'Gerepareerd: ' . $fixed . ' artikelen<br>';
    echo 'Fouten: ' . $errors;
    echo '</div>';
    
    echo '<div class="info">';
    echo '<strong>Volgende stappen:</strong><br>';
    echo '1. Controleer of de artikelen nu zichtbaar zijn in de backend<br>';
    echo '2. Als ze nog steeds niet zichtbaar zijn, controleer ook:<br>';
    echo '   - Published status (state = 1)<br>';
    echo '   - Asset entries<br>';
    echo '   - Access levels';
    echo '</div>';
    
    echo '<a href="compare_visible_article.php" class="btn">Terug naar analyse</a>';
}
?>

</div>
</body>
</html>
