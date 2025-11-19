<?php
/**
 * Script om te analyseren waarom artikel ID 145 wel zichtbaar is
 * en andere artikelen niet - HTML versie voor browser
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

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyse: Waarom is artikel ID 145 wel zichtbaar?</title>
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
            max-width: 1200px;
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
        h2 {
            color: #34495e;
            margin-top: 30px;
            margin-bottom: 15px;
            padding: 10px;
            background: #ecf0f1;
            border-left: 4px solid #3498db;
        }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 15px 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #3498db; color: white; font-weight: 600; }
        tr:hover { background: #f5f5f5; }
        .diff { color: #e74c3c; font-weight: bold; }
        .match { color: #27ae60; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        ul { margin-left: 20px; margin-top: 10px; }
        li { margin: 5px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 0 0; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Analyse: Waarom is artikel ID 145 wel zichtbaar?</h1>
    
    <div class="info">
        <strong>Database prefix:</strong> <?php echo h($prefix); ?>
    </div>

<?php
// 1. Haal het zichtbare artikel op
$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName('ENGINE_content'))
    ->where($db->quoteName('id') . ' = ' . (int)$visibleArticleId);
$db->setQuery($query);
$visibleArticle = $db->loadObject();

if (!$visibleArticle) {
    echo '<div class="error"><strong>✗ Fout:</strong> Artikel ID ' . $visibleArticleId . ' niet gevonden in ENGINE_content!</div>';
    echo '</div></body></html>';
    exit;
}

echo '<h2>1. Zichtbaar artikel (ID ' . $visibleArticleId . ')</h2>';
echo '<div class="success">';
echo '<strong>✓ Artikel gevonden:</strong><br>';
echo '<table>';
echo '<tr><th>Eigenschap</th><th>Waarde</th></tr>';
echo '<tr><td>ID</td><td>' . h($visibleArticle->id) . '</td></tr>';
echo '<tr><td>Titel</td><td>' . h($visibleArticle->title) . '</td></tr>';
echo '<tr><td>State</td><td>' . h($visibleArticle->state) . '</td></tr>';
echo '<tr><td>Asset ID</td><td>' . h($visibleArticle->asset_id ?? 'NULL') . '</td></tr>';
echo '<tr><td>Catid</td><td>' . h($visibleArticle->catid) . '</td></tr>';
echo '<tr><td>Access</td><td>' . h($visibleArticle->access) . '</td></tr>';
echo '<tr><td>Created by</td><td>' . h($visibleArticle->created_by) . '</td></tr>';
echo '<tr><td>Language</td><td>' . h($visibleArticle->language ?? 'NULL') . '</td></tr>';
echo '</table>';
echo '</div>';

// 2. Controleer workflow associations
echo '<h2>2. Workflow Associations</h2>';

$workflowTables = [
    $prefix . 'workflow_associations',
    $prefix . 'workflows',
    $prefix . 'workflow_stages',
    $prefix . 'workflow_transitions'
];

$workflowTablesExist = [];
$tables = $db->getTableList();
foreach ($workflowTables as $table) {
    $exists = false;
    foreach ($tables as $t) {
        if (strtolower($t) === strtolower($table)) {
            $exists = true;
            break;
        }
    }
    $workflowTablesExist[$table] = $exists;
}

echo '<table>';
echo '<tr><th>Tabel</th><th>Status</th></tr>';
foreach ($workflowTablesExist as $table => $exists) {
    echo '<tr><td><code>' . h($table) . '</code></td><td>' . 
         ($exists ? '<span class="match">✓ Bestaat</span>' : '<span class="diff">✗ Bestaat niet</span>') . '</td></tr>';
}
echo '</table>';

$workflowAssociation = null;
if ($workflowTablesExist[$prefix . 'workflow_associations']) {
    // Eerst controleren welke kolommen er zijn in workflow_stages
    $query = "SHOW COLUMNS FROM " . $db->quoteName($prefix . 'workflow_stages');
    $db->setQuery($query);
    $stageColumns = $db->loadColumn();
    
    // Bepaal welke condition kolom te gebruiken
    $conditionColumn = null;
    if (in_array('condition', $stageColumns)) {
        $conditionColumn = 'ws.condition';
    } elseif (in_array('condition_id', $stageColumns)) {
        $conditionColumn = 'ws.condition_id';
    }
    
    // Build query zonder condition eerst
    $query = $db->getQuery(true)
        ->select('wa.*, w.title as workflow_title, ws.title as stage_title');
    
    // Voeg condition kolom toe als die bestaat
    if ($conditionColumn) {
        $query->select($conditionColumn);
    }
    
    $query->from($db->quoteName($prefix . 'workflow_associations', 'wa'))
        ->leftJoin($db->quoteName($prefix . 'workflows', 'w') . ' ON ' . 
                   $db->quoteName('w.id') . ' = ' . $db->quoteName('wa.workflow_id'))
        ->leftJoin($db->quoteName($prefix . 'workflow_stages', 'ws') . ' ON ' . 
                   $db->quoteName('ws.id') . ' = ' . $db->quoteName('wa.stage_id'))
        ->where($db->quoteName('wa.item_id') . ' = ' . (int)$visibleArticleId)
        ->where($db->quoteName('wa.extension') . ' = ' . $db->quote('com_content.article'));
    $db->setQuery($query);
    $workflowAssociation = $db->loadObject();
    
    if ($workflowAssociation) {
        echo '<div class="success">';
        echo '<strong>✓ Workflow association gevonden:</strong><br>';
        echo '<table>';
        echo '<tr><th>Eigenschap</th><th>Waarde</th></tr>';
        echo '<tr><td>Workflow ID</td><td>' . h($workflowAssociation->workflow_id) . '</td></tr>';
        echo '<tr><td>Workflow</td><td>' . h($workflowAssociation->workflow_title) . '</td></tr>';
        echo '<tr><td>Stage ID</td><td>' . h($workflowAssociation->stage_id) . '</td></tr>';
        echo '<tr><td>Stage</td><td>' . h($workflowAssociation->stage_title) . '</td></tr>';
        if (isset($workflowAssociation->condition)) {
            echo '<tr><td>Condition</td><td>' . h($workflowAssociation->condition) . '</td></tr>';
        } elseif (isset($workflowAssociation->condition_id)) {
            echo '<tr><td>Condition ID</td><td>' . h($workflowAssociation->condition_id) . '</td></tr>';
        }
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="warning"><strong>⚠ Waarschuwing:</strong> Geen workflow association gevonden voor artikel ID ' . $visibleArticleId . '!</div>';
    }
} else {
    echo '<div class="warning"><strong>⚠ Waarschuwing:</strong> Workflow tabellen bestaan niet - workflows zijn mogelijk niet actief</div>';
}

// 3. Vergelijk met andere artikelen
echo '<h2>3. Vergelijking met andere artikelen</h2>';

$query = $db->getQuery(true)
    ->select('id, title, state, asset_id, catid, access, created_by, language')
    ->from($db->quoteName('ENGINE_content'))
    ->where($db->quoteName('id') . ' != ' . (int)$visibleArticleId)
    ->setLimit(10);
$db->setQuery($query);
$otherArticles = $db->loadObjectList();

if (count($otherArticles) > 0) {
    echo '<table>';
    echo '<tr><th>ID</th><th>Titel</th><th>State</th><th>Asset ID</th><th>Catid</th><th>Access</th><th>Language</th><th>Workflow</th></tr>';
    
    foreach ($otherArticles as $article) {
        // Controleer workflow
        $otherWorkflow = null;
        if ($workflowTablesExist[$prefix . 'workflow_associations']) {
            // Bepaal condition kolom
            $query = "SHOW COLUMNS FROM " . $db->quoteName($prefix . 'workflow_stages');
            $db->setQuery($query);
            $stageColumns = $db->loadColumn();
            $conditionSelect = '';
            if (in_array('condition', $stageColumns)) {
                $conditionSelect = 'ws.condition';
            } elseif (in_array('condition_id', $stageColumns)) {
                $conditionSelect = 'ws.condition_id';
            }
            
            $query = $db->getQuery(true)
                ->select('wa.workflow_id, wa.stage_id');
            if ($conditionSelect) {
                $query->select($conditionSelect);
            }
            $query->from($db->quoteName($prefix . 'workflow_associations', 'wa'))
                ->leftJoin($db->quoteName($prefix . 'workflow_stages', 'ws') . ' ON ' . 
                           $db->quoteName('ws.id') . ' = ' . $db->quoteName('wa.stage_id'))
                ->where($db->quoteName('wa.item_id') . ' = ' . (int)$article->id)
                ->where($db->quoteName('wa.extension') . ' = ' . $db->quote('com_content.article'));
            $db->setQuery($query);
            $otherWorkflow = $db->loadObject();
        }
        
        $hasWorkflow = $otherWorkflow ? '✓' : '<span class="diff">✗ MISSEND</span>';
        $stateDiff = $article->state != $visibleArticle->state ? ' <span class="diff">⚠</span>' : '';
        $assetDiff = (($article->asset_id ?? 0) != ($visibleArticle->asset_id ?? 0)) ? ' <span class="diff">⚠</span>' : '';
        
        echo '<tr>';
        echo '<td>' . h($article->id) . '</td>';
        echo '<td>' . h($article->title) . '</td>';
        echo '<td>' . h($article->state) . $stateDiff . '</td>';
        echo '<td>' . h($article->asset_id ?? 'NULL') . $assetDiff . '</td>';
        echo '<td>' . h($article->catid) . '</td>';
        echo '<td>' . h($article->access) . '</td>';
        echo '<td>' . h($article->language ?? 'NULL') . '</td>';
        echo '<td>' . $hasWorkflow . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// 4. Tel artikelen zonder workflow
if ($workflowTablesExist[$prefix . 'workflow_associations']) {
    echo '<h2>4. Artikelen zonder workflow association</h2>';
    
    $query = $db->getQuery(true)
        ->select('COUNT(DISTINCT c.id) as total')
        ->from($db->quoteName('ENGINE_content', 'c'))
        ->leftJoin($db->quoteName($prefix . 'workflow_associations', 'wa') . ' ON ' . 
                   $db->quoteName('wa.item_id') . ' = ' . $db->quoteName('c.id') . 
                   ' AND ' . $db->quoteName('wa.extension') . ' = ' . $db->quote('com_content.article'))
        ->where($db->quoteName('wa.id') . ' IS NULL');
    $db->setQuery($query);
    $articlesWithoutWorkflow = $db->loadResult();
    
    if ($articlesWithoutWorkflow > 0) {
        echo '<div class="error">';
        echo '<strong>⚠⚠⚠ PROBLEEM GEVONDEN!</strong><br>';
        echo '<strong>' . $articlesWithoutWorkflow . '</strong> artikelen hebben GEEN workflow association!<br>';
        echo 'Dit is waarschijnlijk de oorzaak waarom ze niet zichtbaar zijn.';
        echo '</div>';
    } else {
        echo '<div class="success">✓ Alle artikelen hebben een workflow association</div>';
    }
}

// 5. Workflow stages
if ($workflowTablesExist[$prefix . 'workflow_stages']) {
    echo '<h2>5. Beschikbare workflow stages</h2>';
    
    // Bepaal welke condition kolom te gebruiken
    $query = "SHOW COLUMNS FROM " . $db->quoteName($prefix . 'workflow_stages');
    $db->setQuery($query);
    $stageColumns = $db->loadColumn();
    
    $query = $db->getQuery(true)
        ->select('id, title, workflow_id');
    
    if (in_array('condition', $stageColumns)) {
        $query->select('condition');
    } elseif (in_array('condition_id', $stageColumns)) {
        $query->select('condition_id');
    }
    
    if (in_array('ordering', $stageColumns)) {
        $query->order($db->quoteName('workflow_id') . ', ' . $db->quoteName('ordering'));
    } else {
        $query->order($db->quoteName('workflow_id'));
    }
    
    $query->from($db->quoteName($prefix . 'workflow_stages'));
    $db->setQuery($query);
    $stages = $db->loadObjectList();
    
    if (count($stages) > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Titel</th>';
        if (in_array('condition', $stageColumns) || in_array('condition_id', $stageColumns)) {
            echo '<th>Condition</th>';
        }
        echo '<th>Workflow ID</th></tr>';
        foreach ($stages as $stage) {
            $conditionValue = $stage->condition ?? $stage->condition_id ?? null;
            $conditionName = ['0' => 'Unpublished', '1' => 'Published', '-2' => 'Trashed'];
            $condition = $conditionValue !== null ? ($conditionName[$conditionValue] ?? "Unknown ({$conditionValue})") : 'N/A';
            
            echo '<tr>';
            echo '<td>' . h($stage->id) . '</td>';
            echo '<td>' . h($stage->title) . '</td>';
            if (in_array('condition', $stageColumns) || in_array('condition_id', $stageColumns)) {
                echo '<td>' . h($condition) . '</td>';
            }
            echo '<td>' . h($stage->workflow_id) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}

// 6. Samenvatting
echo '<h2>6. Samenvatting en aanbevelingen</h2>';

$problems = [];
if ($workflowTablesExist[$prefix . 'workflow_associations']) {
    if (isset($articlesWithoutWorkflow) && $articlesWithoutWorkflow > 0) {
        $problems[] = $articlesWithoutWorkflow . ' artikelen hebben GEEN workflow association - dit is waarschijnlijk de hoofdoorzaak!';
    }
}

if (count($problems) > 0) {
    echo '<div class="error">';
    echo '<strong>PROBLEMEN:</strong><ul>';
    foreach ($problems as $problem) {
        echo '<li>' . h($problem) . '</li>';
    }
    echo '</ul>';
    echo '<strong>OPLOSSING:</strong><br>';
    echo 'Voer <code>fix_workflow_associations.php</code> uit om workflow associations aan te maken voor alle artikelen die deze missen.';
    echo '</div>';
    echo '<a href="fix_workflow_associations.php" class="btn">🔧 Repareer Workflow Associations</a>';
} else {
    echo '<div class="success">✓ Geen duidelijke workflow problemen gevonden. Controleer andere verschillen tussen zichtbaar en niet-zichtbaar artikel.</div>';
}

if (isset($workflowAssociation) && $workflowAssociation) {
    echo '<div class="info">';
    echo '<strong>Het zichtbare artikel heeft:</strong><br>';
    echo 'Workflow ID: ' . h($workflowAssociation->workflow_id) . '<br>';
    echo 'Stage ID: ' . h($workflowAssociation->stage_id) . '<br>';
    if (isset($workflowAssociation->condition)) {
        echo 'Stage Condition: ' . h($workflowAssociation->condition);
    } elseif (isset($workflowAssociation->condition_id)) {
        echo 'Stage Condition ID: ' . h($workflowAssociation->condition_id);
    }
    echo '</div>';
}

echo '</div></body></html>';
