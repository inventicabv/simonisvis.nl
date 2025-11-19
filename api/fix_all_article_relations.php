<?php
/**
 * Script om alle relaties te repareren voor artikelen in ENGINE_content
 * 
 * Dit script repareert:
 * 1. Ontbrekende asset entries
 * 2. Ontbrekende UCM content records
 * 3. Ontbrekende UCM base records
 * 4. Ontbrekende workflow associations (vereist voor backend zichtbaarheid)
 * 5. Asset tree rebuild
 * 6. Content type verificatie
 */

// Laad Joomla framework
define('_JEXEC', 1);
define('JPATH_BASE', dirname(__DIR__));

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Table\ContentType;

// Haal database connectie op
$db = Factory::getContainer()->get(DatabaseInterface::class);
$prefix = $db->getPrefix();

// HTML header
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparatie Artikel Relaties</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .content { padding: 30px; }
        .section {
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }
        .section-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 2px solid #e74c3c;
            font-weight: 600;
            font-size: 16px;
        }
        .section-content { padding: 20px; }
        .success { color: #28a745; font-weight: 600; margin: 10px 0; }
        .error { color: #dc3545; font-weight: 600; margin: 10px 0; }
        .warning { color: #ffc107; font-weight: 600; margin: 10px 0; }
        .info { color: #17a2b8; font-weight: 600; margin: 10px 0; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #e74c3c;
        }
        .stat-label { font-size: 12px; color: #666; text-transform: uppercase; }
        .stat-value { font-size: 24px; font-weight: 600; color: #333; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        table tr:hover { background: #f8f9fa; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            margin-top: 20px;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #c0392b; }
        .progress {
            background: #e0e0e0;
            border-radius: 4px;
            height: 30px;
            margin: 10px 0;
            overflow: hidden;
        }
        .progress-bar {
            background: #28a745;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Reparatie Artikel Relaties</h1>
            <div>Database prefix: <?php echo htmlspecialchars($prefix); ?></div>
        </div>
        <div class="content">
<?php

// Check of reparatie moet worden uitgevoerd
$doFix = isset($_GET['fix']) && $_GET['fix'] === 'yes';

if (!$doFix) {
    // Diagnose modus
    echo '<div class="section">';
    echo '<div class="section-header">📊 Diagnose</div>';
    echo '<div class="section-content">';
    echo '<p>Dit script controleert en repareert alle relaties voor artikelen in ENGINE_content.</p>';
    echo '<p><strong>WAARSCHUWING:</strong> Dit script maakt database wijzigingen. Maak eerst een backup!</p>';
    echo '<a href="?fix=yes" class="btn">Start Reparatie</a>';
    echo '</div></div>';
}

// 1. Diagnose: Tel artikelen
$query = $db->getQuery(true)
    ->select('COUNT(*) as total')
    ->from($db->quoteName($prefix . 'content'));
$db->setQuery($query);
$totalArticles = $db->loadResult();

echo '<div class="section">';
echo '<div class="section-header">1. Artikelen in ' . htmlspecialchars($prefix) . 'content</div>';
echo '<div class="section-content">';
echo '<div class="stats">';
echo '<div class="stat-box"><div class="stat-label">Totaal Artikelen</div><div class="stat-value">' . $totalArticles . '</div></div>';

// Artikelen zonder asset_id
$query = $db->getQuery(true)
    ->select('COUNT(*) as total')
    ->from($db->quoteName($prefix . 'content'))
    ->where($db->quoteName('asset_id') . ' = 0 OR ' . $db->quoteName('asset_id') . ' IS NULL');
$db->setQuery($query);
$articlesWithoutAssetId = $db->loadResult();

// Artikelen zonder asset entry
$query = $db->getQuery(true)
    ->select('COUNT(*) as total')
    ->from($db->quoteName($prefix . 'content', 'c'))
    ->leftJoin($db->quoteName($prefix . 'assets', 'a') . ' ON ' . 
               $db->quoteName('a.id') . ' = ' . $db->quoteName('c.asset_id'))
    ->where($db->quoteName('a.id') . ' IS NULL');
$db->setQuery($query);
$articlesWithoutAssetEntry = $db->loadResult();

// Artikelen zonder UCM content
$query = $db->getQuery(true)
    ->select('COUNT(*) as total')
    ->from($db->quoteName($prefix . 'content', 'c'))
    ->leftJoin($db->quoteName($prefix . 'ucm_content', 'ucm') . ' ON ' . 
               $db->quoteName('ucm.core_content_item_id') . ' = ' . $db->quoteName('c.id') . 
               ' AND ' . $db->quoteName('ucm.core_type_alias') . ' = ' . $db->quote('com_content.article'))
    ->where($db->quoteName('ucm.core_content_id') . ' IS NULL');
$db->setQuery($query);
$articlesWithoutUCM = $db->loadResult();

// Artikelen zonder workflow association
$articlesWithoutWorkflow = 0;
$workflowTables = [
    $prefix . 'workflow_associations',
    $prefix . 'workflows',
    $prefix . 'workflow_stages'
];
$tables = $db->getTableList();
$allWorkflowTablesExist = true;
foreach ($workflowTables as $table) {
    $exists = false;
    foreach ($tables as $t) {
        if (strtolower($t) === strtolower($table)) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $allWorkflowTablesExist = false;
        break;
    }
}

if ($allWorkflowTablesExist) {
    $query = $db->getQuery(true)
        ->select('COUNT(*) as total')
        ->from($db->quoteName($prefix . 'content', 'c'))
        ->leftJoin($db->quoteName($prefix . 'workflow_associations', 'wa') . ' ON ' . 
                   $db->quoteName('wa.item_id') . ' = ' . $db->quoteName('c.id') . 
                   ' AND ' . $db->quoteName('wa.extension') . ' = ' . $db->quote('com_content.article'))
        ->where($db->quoteName('wa.item_id') . ' IS NULL');
    $db->setQuery($query);
    $articlesWithoutWorkflow = $db->loadResult();
}

echo '<div class="stat-box"><div class="stat-label">Zonder Asset ID</div><div class="stat-value" style="color: #dc3545;">' . $articlesWithoutAssetId . '</div></div>';
echo '<div class="stat-box"><div class="stat-label">Zonder Asset Entry</div><div class="stat-value" style="color: #dc3545;">' . $articlesWithoutAssetEntry . '</div></div>';
echo '<div class="stat-box"><div class="stat-label">Zonder UCM Record</div><div class="stat-value" style="color: #dc3545;">' . $articlesWithoutUCM . '</div></div>';
if ($allWorkflowTablesExist) {
    echo '<div class="stat-box"><div class="stat-label">Zonder Workflow Association</div><div class="stat-value" style="color: #dc3545;">' . $articlesWithoutWorkflow . '</div></div>';
}
echo '</div>';
echo '</div></div>';

if ($doFix) {
    $fixedAssets = 0;
    $fixedUCM = 0;
    $fixedWorkflows = 0;
    $errors = [];
    
    // Haal parent asset op
    $query = $db->getQuery(true)
        ->select('id')
        ->from($db->quoteName($prefix . 'assets'))
        ->where($db->quoteName('name') . ' = ' . $db->quote('com_content'));
    $db->setQuery($query);
    $parentAssetId = $db->loadResult();
    
    if (!$parentAssetId) {
        echo '<div class="section">';
        echo '<div class="section-header">❌ Fout</div>';
        echo '<div class="section-content">';
        echo '<div class="error">Parent asset "com_content" niet gevonden!</div>';
        echo '</div></div>';
    } else {
        echo '<div class="section">';
        echo '<div class="section-header">2. Reparatie Assets</div>';
        echo '<div class="section-content">';
        echo '<div class="info">Parent asset ID: ' . $parentAssetId . '</div>';
        
        // Haal content type op
        $query = $db->getQuery(true)
            ->select('type_id')
            ->from($db->quoteName($prefix . 'content_types'))
            ->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_content.article'));
        $db->setQuery($query);
        $contentTypeId = $db->loadResult();
        
        if (!$contentTypeId) {
            echo '<div class="warning">⚠ Content type "com_content.article" niet gevonden, wordt aangemaakt...</div>';
            
            // Probeer content type aan te maken
            try {
                $contentType = new \stdClass();
                $contentType->type_title = 'Article';
                $contentType->type_alias = 'com_content.article';
                $contentType->table = new \stdClass();
                $contentType->table->special = new \stdClass();
                $contentType->table->special->dbtable = '#__content';
                $contentType->table->special->key = 'id';
                $contentType->table->special->type = 'Content';
                $contentType->table->special->prefix = 'ContentTable';
                $contentType->table->common = new \stdClass();
                $contentType->table->common->dbtable = '#__ucm_content';
                $contentType->table->common->key = 'ucm_id';
                $contentType->table->common->type = 'Corecontent';
                $contentType->table->common->prefix = 'JTable';
                $contentType->field_mappings = new \stdClass();
                $contentType->field_mappings->common = new \stdClass();
                $contentType->field_mappings->common->core_content_item_id = 'id';
                $contentType->field_mappings->common->core_title = 'title';
                $contentType->field_mappings->common->core_state = 'state';
                $contentType->field_mappings->common->core_alias = 'alias';
                $contentType->field_mappings->common->core_created_time = 'created';
                $contentType->field_mappings->common->core_modified_time = 'modified';
                $contentType->field_mappings->common->core_body = 'introtext';
                $contentType->field_mappings->common->core_hits = 'hits';
                $contentType->field_mappings->common->core_publish_up = 'publish_up';
                $contentType->field_mappings->common->core_publish_down = 'publish_down';
                $contentType->field_mappings->common->core_access = 'access';
                $contentType->field_mappings->common->core_params = 'attribs';
                $contentType->field_mappings->common->core_featured = 'featured';
                $contentType->field_mappings->common->core_metadata = 'metadata';
                $contentType->field_mappings->common->core_language = 'language';
                $contentType->field_mappings->common->core_images = 'images';
                $contentType->field_mappings->common->core_urls = 'urls';
                $contentType->field_mappings->common->core_version = 'version';
                $contentType->field_mappings->common->core_ordering = 'ordering';
                $contentType->field_mappings->common->core_metakey = 'metakey';
                $contentType->field_mappings->common->core_metadesc = 'metadesc';
                $contentType->field_mappings->common->core_catid = 'catid';
                $contentType->field_mappings->common->core_xreference = 'xreference';
                $contentType->field_mappings->common->asset_id = 'asset_id';
                $contentType->field_mappings->special = new \stdClass();
                $contentType->field_mappings->special->fulltext = 'fulltext';
                $contentType->content_history_options = new \stdClass();
                
                $contentTypeTable = new ContentType($db);
                $contentTypeTable->type_title = 'Article';
                $contentTypeTable->type_alias = 'com_content.article';
                $contentTypeTable->table = json_encode($contentType->table);
                $contentTypeTable->fieldmappings = json_encode($contentType->field_mappings);
                $contentTypeTable->content_history_options = json_encode($contentType->content_history_options);
                $contentTypeTable->router = 'ContentHelperRoute::getArticleRoute';
                $contentTypeTable->rules = '';
                
                if ($contentTypeTable->check() && $contentTypeTable->store()) {
                    $contentTypeId = $contentTypeTable->type_id;
                    echo '<div class="success">✓ Content type succesvol aangemaakt (ID: ' . $contentTypeId . ')</div>';
                } else {
                    throw new Exception($contentTypeTable->getError() ?: 'Onbekende fout bij aanmaken content type');
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ Kon content type niet aanmaken: ' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<div class="info">UCM records kunnen niet worden aangemaakt zonder content type. Assets kunnen wel worden gerepareerd.</div>';
            }
        }
        
        // Haal artikelen op die gerepareerd moeten worden
        // Artikelen zonder asset entry OF zonder UCM record
        $query = $db->getQuery(true)
            ->select('c.*')
            ->from($db->quoteName($prefix . 'content', 'c'))
            ->leftJoin($db->quoteName($prefix . 'assets', 'a') . ' ON ' . 
                       $db->quoteName('a.id') . ' = ' . $db->quoteName('c.asset_id'))
            ->leftJoin($db->quoteName($prefix . 'ucm_content', 'ucm') . ' ON ' . 
                       $db->quoteName('ucm.core_content_item_id') . ' = ' . $db->quoteName('c.id') . 
                       ' AND ' . $db->quoteName('ucm.core_type_alias') . ' = ' . $db->quote('com_content.article'))
            ->where('(' . $db->quoteName('a.id') . ' IS NULL OR ' . $db->quoteName('ucm.core_content_id') . ' IS NULL)');
        $db->setQuery($query);
        $articlesToFix = $db->loadObjectList();
        
        echo '<div class="info">Te repareren artikelen: ' . count($articlesToFix) . '</div>';
        
        if (count($articlesToFix) > 0) {
            echo '<div class="progress">';
            echo '<div class="progress-bar" style="width: 0%;" id="progress">0%</div>';
            echo '</div>';
            
            $total = count($articlesToFix);
            $current = 0;
            
            foreach ($articlesToFix as $article) {
                try {
                    $current++;
                    $percent = round(($current / $total) * 100);
                    
                    // Check of asset bestaat
                    $assetId = $article->asset_id;
                    $needsAsset = false;
                    
                    if (!$assetId || $assetId == 0) {
                        $needsAsset = true;
                    } else {
                        // Verifieer dat asset echt bestaat
                        $query = $db->getQuery(true)
                            ->select('id')
                            ->from($db->quoteName($prefix . 'assets'))
                            ->where($db->quoteName('id') . ' = ' . (int)$assetId);
                        $db->setQuery($query);
                        $assetExists = $db->loadResult();
                        if (!$assetExists) {
                            $needsAsset = true;
                            $assetId = null;
                        }
                    }
                    
                    // Maak asset entry als deze ontbreekt
                    if ($needsAsset) {
                        // Maak asset naam
                        $assetName = 'com_content.article.' . $article->id;
                        
                        // Bepaal parent asset (categorie of com_content)
                        $categoryAssetId = $parentAssetId;
                        if ($article->catid) {
                            $query = $db->getQuery(true)
                                ->select('asset_id')
                                ->from($db->quoteName($prefix . 'categories'))
                                ->where($db->quoteName('id') . ' = ' . (int)$article->catid);
                            $db->setQuery($query);
                            $catAssetId = $db->loadResult();
                            if ($catAssetId) {
                                $categoryAssetId = $catAssetId;
                            }
                        }
                        
                        // Maak asset entry
                        $asset = new \stdClass();
                        $asset->parent_id = $categoryAssetId;
                        $asset->lft = 0;
                        $asset->rgt = 0;
                        $asset->level = 0;
                        $asset->name = $assetName;
                        $asset->title = $article->title;
                        $asset->rules = '{}';
                        
                        $db->insertObject($prefix . 'assets', $asset);
                        $assetId = $db->insertid();
                        
                        if (!$assetId) {
                            throw new Exception("Kon asset niet aanmaken");
                        }
                        
                        // Update artikel met asset_id
                        $query = $db->getQuery(true)
                            ->update($db->quoteName($prefix . 'content'))
                            ->set($db->quoteName('asset_id') . ' = ' . (int)$assetId)
                            ->where($db->quoteName('id') . ' = ' . (int)$article->id);
                        $db->setQuery($query);
                        $db->execute();
                        
                        $fixedAssets++;
                    }
                    
                    // Maak UCM content record als content type bestaat
                    if ($contentTypeId) {
                        // Check of UCM record al bestaat
                        $query = $db->getQuery(true)
                            ->select('core_content_id')
                            ->from($db->quoteName($prefix . 'ucm_content'))
                            ->where($db->quoteName('core_content_item_id') . ' = ' . (int)$article->id)
                            ->where($db->quoteName('core_type_alias') . ' = ' . $db->quote('com_content.article'));
                        $db->setQuery($query);
                        $existingUCM = $db->loadResult();
                        
                        if (!$existingUCM) {
                            // Combineer introtext en fulltext voor core_body
                            $coreBody = '';
                            if (!empty($article->introtext)) {
                                $coreBody = $article->introtext;
                            }
                            if (!empty($article->fulltext)) {
                                $coreBody .= ($coreBody ? ' ' : '') . $article->fulltext;
                            }
                            
                            // Maak UCM content record
                            $ucmContent = new \stdClass();
                            $ucmContent->core_type_alias = 'com_content.article';
                            $ucmContent->core_type_id = $contentTypeId;
                            $ucmContent->core_content_item_id = $article->id;
                            $ucmContent->core_title = $article->title ?? '';
                            $ucmContent->core_alias = $article->alias ?? '';
                            $ucmContent->core_body = $coreBody;
                            $ucmContent->core_state = $article->state ?? 0;
                            $ucmContent->core_access = $article->access ?? 1;
                            $ucmContent->core_created_user_id = $article->created_by ?? 0;
                            $ucmContent->core_created_time = $article->created ?? Factory::getDate()->toSql();
                            $ucmContent->core_modified_user_id = $article->modified_by ?? 0;
                            $ucmContent->core_modified_time = $article->modified ?? $db->getNullDate();
                            $ucmContent->core_language = $article->language ?? '*';
                            $ucmContent->core_publish_up = $article->publish_up ?? $db->getNullDate();
                            $ucmContent->core_publish_down = $article->publish_down ?? $db->getNullDate();
                            $ucmContent->core_images = $article->images ?? '{}';
                            $ucmContent->core_urls = $article->urls ?? '{}';
                            $ucmContent->core_metadata = $article->metadata ?? '{}';
                            $ucmContent->core_version = $article->version ?? 1;
                            $ucmContent->core_ordering = $article->ordering ?? 0;
                            $ucmContent->core_metakey = $article->metakey ?? '';
                            $ucmContent->core_metadesc = $article->metadesc ?? '';
                            $ucmContent->core_catid = $article->catid ?? 0;
                            $ucmContent->core_xreference = '';
                            $ucmContent->asset_id = $assetId;
                            
                            $db->insertObject($prefix . 'ucm_content', $ucmContent);
                            $ucmId = $db->insertid();
                            
                            if ($ucmId) {
                                // Maak UCM base record
                                $ucmBase = new \stdClass();
                                $ucmBase->ucm_id = $ucmId;
                                $ucmBase->ucm_item_id = $article->id;
                                $ucmBase->ucm_type_id = $contentTypeId;
                                
                                // Haal language ID op
                                $langId = 0;
                                if ($article->language && $article->language != '*') {
                                    $query = $db->getQuery(true)
                                        ->select('lang_id')
                                        ->from($db->quoteName($prefix . 'languages'))
                                        ->where($db->quoteName('lang_code') . ' = ' . $db->quote($article->language));
                                    $db->setQuery($query);
                                    $langId = $db->loadResult() ?: 0;
                                }
                                
                                $ucmBase->ucm_language_id = $langId;
                                
                                $db->insertObject($prefix . 'ucm_base', $ucmBase);
                                $fixedUCM++;
                            }
                        }
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Artikel ID {$article->id}: " . $e->getMessage();
                }
            }
            
            echo '<div class="success">✓ ' . $fixedAssets . ' assets gerepareerd</div>';
            echo '<div class="success">✓ ' . $fixedUCM . ' UCM records aangemaakt</div>';
        } else {
            echo '<div class="success">✓ Geen artikelen gevonden die gerepareerd moeten worden</div>';
        }
        
        // Repareer workflow associations (altijd uitvoeren, onafhankelijk van andere reparaties)
        echo '<div class="section">';
        echo '<div class="section-header">4. Reparatie Workflow Associations</div>';
        echo '<div class="section-content">';
        
        $fixedWorkflows = 0;
        
        // Controleer of workflow tabellen bestaan
        $workflowTables = [
            $prefix . 'workflow_associations',
            $prefix . 'workflows',
            $prefix . 'workflow_stages'
        ];
        
        $tables = $db->getTableList();
        $allTablesExist = true;
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
            // Haal referentie workflow op van een bestaand artikel (bijv. ID 145 of 207)
            $referenceArticleIds = [145, 207];
            $referenceWorkflow = null;
            
            foreach ($referenceArticleIds as $refId) {
                $query = $db->getQuery(true)
                    ->select('wa.stage_id')
                    ->from($db->quoteName($prefix . 'workflow_associations', 'wa'))
                    ->where($db->quoteName('wa.item_id') . ' = ' . (int)$refId)
                    ->where($db->quoteName('wa.extension') . ' = ' . $db->quote('com_content.article'));
                $db->setQuery($query);
                $stageId = $db->loadResult();
                
                if ($stageId) {
                    $referenceWorkflow = (object)['stage_id' => $stageId];
                    break;
                }
            }
            
            if ($referenceWorkflow) {
                // Haal alle artikelen op zonder workflow association
                $query = $db->getQuery(true)
                    ->select('c.id, c.title')
                    ->from($db->quoteName($prefix . 'content', 'c'))
                    ->leftJoin($db->quoteName($prefix . 'workflow_associations', 'wa') . ' ON ' . 
                               $db->quoteName('wa.item_id') . ' = ' . $db->quoteName('c.id') . 
                               ' AND ' . $db->quoteName('wa.extension') . ' = ' . $db->quote('com_content.article'))
                    ->where($db->quoteName('wa.item_id') . ' IS NULL');
                $db->setQuery($query);
                $articlesWithoutWorkflow = $db->loadObjectList();
                
                echo '<div class="info">Te repareren artikelen zonder workflow: ' . count($articlesWithoutWorkflow) . '</div>';
                
                if (count($articlesWithoutWorkflow) > 0) {
                    foreach ($articlesWithoutWorkflow as $article) {
                        try {
                            // Controleer of al bestaat
                            $query = $db->getQuery(true)
                                ->select($db->quoteName('item_id'))
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
                                $fixedWorkflows++;
                            }
                        } catch (Exception $e) {
                            $errors[] = "Workflow artikel ID {$article->id}: " . $e->getMessage();
                        }
                    }
                    
                    echo '<div class="success">✓ ' . $fixedWorkflows . ' workflow associations aangemaakt</div>';
                } else {
                    echo '<div class="success">✓ Geen artikelen gevonden zonder workflow association</div>';
                }
            } else {
                echo '<div class="warning">⚠ Geen referentie workflow gevonden. Artikelen 145 en 207 hebben mogelijk geen workflow association.</div>';
            }
        } else {
            echo '<div class="warning">⚠ Workflow tabellen niet gevonden. Workflow associations worden overgeslagen.</div>';
        }
        
        echo '</div></div>';
        
        // Rebuild assets tree (altijd uitvoeren)
        echo '<div class="section">';
        echo '<div class="section-header">5. Assets Tree Rebuild</div>';
        echo '<div class="section-content">';
        
        try {
            // Gebruik Joomla's Asset table rebuild
            Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_content/src/Table');
            $assetTable = Table::getInstance('Asset', 'Joomla\\CMS\\Table\\', ['dbo' => $db]);
            
            if ($assetTable && method_exists($assetTable, 'rebuild')) {
                $assetTable->rebuild();
                echo '<div class="success">✓ Assets tree succesvol gerebuild</div>';
            } else {
                // Alternatieve methode: gebruik Access helper
                Access::clearStatics();
                echo '<div class="success">✓ Assets cache geleegd</div>';
                echo '<div class="info">Voor volledige rebuild: Extensions > Manage > Database > Rebuild Assets</div>';
            }
        } catch (Exception $e) {
            echo '<div class="warning">⚠ Kon assets tree niet automatisch rebuilden: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="info">Ga naar: Extensions > Manage > Database > Rebuild Assets</div>';
        }
        
        echo '</div></div>';
        
        // Toon errors
        if (count($errors) > 0) {
            echo '<div class="section">';
            echo '<div class="section-header">❌ Fouten</div>';
            echo '<div class="section-content">';
            echo '<table><tr><th>Fout</th></tr>';
            foreach ($errors as $error) {
                echo '<tr><td>' . htmlspecialchars($error) . '</td></tr>';
            }
            echo '</table>';
            echo '</div></div>';
        }
        
        // Samenvatting
        echo '<div class="section">';
        echo '<div class="section-header">✅ Samenvatting</div>';
        echo '<div class="section-content">';
        echo '<div class="stats">';
        echo '<div class="stat-box"><div class="stat-label">Assets Gerepareerd</div><div class="stat-value" style="color: #28a745;">' . $fixedAssets . '</div></div>';
        echo '<div class="stat-box"><div class="stat-label">UCM Records</div><div class="stat-value" style="color: #28a745;">' . $fixedUCM . '</div></div>';
        echo '<div class="stat-box"><div class="stat-label">Workflow Associations</div><div class="stat-value" style="color: #28a745;">' . $fixedWorkflows . '</div></div>';
        echo '<div class="stat-box"><div class="stat-label">Fouten</div><div class="stat-value" style="color: #dc3545;">' . count($errors) . '</div></div>';
        echo '</div>';
        echo '<div class="info" style="margin-top: 20px;">';
        echo '<p><strong>Volgende stappen:</strong></p>';
        echo '<ul style="margin-left: 20px; margin-top: 10px;">';
        echo '<li>Controleer of artikelen nu zichtbaar zijn in de backend</li>';
        echo '<li>Als ze nog niet zichtbaar zijn, controleer de published status (state = 1)</li>';
        echo '<li>Controleer access levels en categorie relaties</li>';
        echo '</ul>';
        echo '</div>';
        echo '</div></div>';
    }
}

?>
        </div>
    </div>
</body>
</html>

