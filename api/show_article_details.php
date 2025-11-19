<?php
/**
 * Script om alle database records te tonen voor een specifiek artikel
 * Toont alle relaties en gerelateerde data
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

// Titel van het artikel
$articleTitle = 'Nieuwe locatie';

// HTML header met styling
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Records: <?php echo htmlspecialchars($articleTitle); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding: 20px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header .subtitle {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }
        .section-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 2px solid #667eea;
            font-weight: 600;
            font-size: 16px;
            color: #333;
        }
        .section-content {
            padding: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #667eea;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
            font-size: 14px;
            word-break: break-word;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        .status-unpublished {
            background: #fff3cd;
            color: #856404;
        }
        .status-trashed {
            background: #f8d7da;
            color: #721c24;
        }
        .success {
            color: #28a745;
            font-weight: 600;
        }
        .warning {
            color: #ffc107;
            font-weight: 600;
        }
        .error {
            color: #dc3545;
            font-weight: 600;
        }
        .info {
            color: #17a2b8;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
        table tr:hover {
            background: #f8f9fa;
        }
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 6px;
            margin-top: 30px;
        }
        .summary h2 {
            margin-bottom: 15px;
            font-size: 20px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .summary-item {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 4px;
        }
        .summary-label {
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 18px;
            font-weight: 600;
        }
        .json-preview {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 100px;
            overflow: auto;
            word-break: break-all;
        }
        .empty {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Database Records voor Artikel</h1>
            <div class="subtitle"><?php echo htmlspecialchars($articleTitle); ?> | Database prefix: <?php echo htmlspecialchars($prefix); ?></div>
        </div>
        <div class="content">
<?php

// 1. Zoek het artikel in de content tabel
$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName($prefix . 'content'))
    ->where($db->quoteName('title') . ' = ' . $db->quote($articleTitle))
    ->order($db->quoteName('id') . ' DESC')
    ->setLimit(1);

$db->setQuery($query);
$article = $db->loadObject();

if (!$article) {
    echo '<div class="section">';
    echo '<div class="section-header">❌ Artikel niet gevonden</div>';
    echo '<div class="section-content">';
    echo '<p>Zoeken naar artikelen met vergelijkbare titel...</p>';
    
    $query = $db->getQuery(true)
        ->select('id, title, alias, state, created')
        ->from($db->quoteName($prefix . 'content'))
        ->where($db->quoteName('title') . ' LIKE ' . $db->quote('%' . $articleTitle . '%'))
        ->order($db->quoteName('id') . ' DESC')
        ->setLimit(5);
    
    $db->setQuery($query);
    $similar = $db->loadObjectList();
    
    if ($similar) {
        echo '<table><tr><th>ID</th><th>Titel</th><th>Alias</th><th>Status</th><th>Gemaakt</th></tr>';
        foreach ($similar as $sim) {
            $status = $sim->state == 1 ? '<span class="status-badge status-published">Gepubliceerd</span>' : 
                     ($sim->state == 0 ? '<span class="status-badge status-unpublished">Ongepubliceerd</span>' : 
                     '<span class="status-badge status-trashed">Prullenbak</span>');
            echo "<tr><td>{$sim->id}</td><td>" . htmlspecialchars($sim->title) . "</td><td>" . htmlspecialchars($sim->alias) . "</td><td>{$status}</td><td>{$sim->created}</td></tr>";
        }
        echo '</table>';
    }
    echo '</div></div></div></div></body></html>';
    exit;
}

$articleId = $article->id;
$assetId = $article->asset_id;

// Helper functie voor status badge
function getStatusBadge($state) {
    if ($state == 1) return '<span class="status-badge status-published">Gepubliceerd</span>';
    if ($state == 0) return '<span class="status-badge status-unpublished">Ongepubliceerd</span>';
    return '<span class="status-badge status-trashed">Prullenbak</span>';
}

// Helper functie voor info item
function infoItem($label, $value, $class = '') {
    return '<div class="info-item ' . $class . '"><div class="info-label">' . htmlspecialchars($label) . '</div><div class="info-value">' . $value . '</div></div>';
}

// 1. Hoofdtabel
echo '<div class="section">';
echo '<div class="section-header">1. HOOFDTABEL: ' . htmlspecialchars($prefix) . 'content</div>';
echo '<div class="section-content">';
echo '<div class="success">✓ Artikel gevonden!</div>';
echo '<div class="info-grid" style="margin-top: 20px;">';
echo infoItem('ID', $article->id);
echo infoItem('Asset ID', $article->asset_id);
echo infoItem('Titel', htmlspecialchars($article->title));
echo infoItem('Alias', htmlspecialchars($article->alias));
echo infoItem('Categorie ID', $article->catid);
echo infoItem('Status', getStatusBadge($article->state));
echo infoItem('Toegang (access)', $article->access);
echo infoItem('Gemaakt door', $article->created_by);
echo infoItem('Gemaakt op', $article->created);
echo infoItem('Gewijzigd door', $article->modified_by ?: '<span class="empty">Niet gewijzigd</span>');
echo infoItem('Gewijzigd op', $article->modified ?: '<span class="empty">Niet gewijzigd</span>');
echo infoItem('Versie', $article->version);
echo infoItem('Hits', $article->hits);
echo infoItem('Taal', $article->language);
echo infoItem('Featured', $article->featured ? '<span class="success">Ja</span>' : '<span class="empty">Nee</span>');
echo infoItem('Publish up', $article->publish_up ?: '<span class="empty">Geen</span>');
echo infoItem('Publish down', $article->publish_down ?: '<span class="empty">Geen</span>');
echo infoItem('Checked out', $article->checked_out ?: '<span class="empty">Niet uitgecheckt</span>');
echo infoItem('Checked out time', $article->checked_out_time ?: '<span class="empty">Geen</span>');
echo infoItem('Ordering', $article->ordering);
echo infoItem('Metakey', htmlspecialchars($article->metakey ?: 'Geen'));
echo infoItem('Metadesc', htmlspecialchars($article->metadesc ?: 'Geen'));
echo '</div>';

if ($article->images && $article->images != '{}') {
    echo '<div style="margin-top: 15px;"><div class="info-label">Images (JSON)</div><div class="json-preview">' . htmlspecialchars(substr($article->images, 0, 500)) . '</div></div>';
}
if ($article->urls && $article->urls != '{}') {
    echo '<div style="margin-top: 15px;"><div class="info-label">URLs (JSON)</div><div class="json-preview">' . htmlspecialchars(substr($article->urls, 0, 500)) . '</div></div>';
}
if ($article->attribs && $article->attribs != '{}') {
    echo '<div style="margin-top: 15px;"><div class="info-label">Attribs (JSON)</div><div class="json-preview">' . htmlspecialchars(substr($article->attribs, 0, 500)) . '</div></div>';
}
if ($article->metadata && $article->metadata != '{}') {
    echo '<div style="margin-top: 15px;"><div class="info-label">Metadata (JSON)</div><div class="json-preview">' . htmlspecialchars(substr($article->metadata, 0, 500)) . '</div></div>';
}
if ($article->introtext) {
    echo '<div style="margin-top: 15px;"><div class="info-label">Introtext</div><div style="background: #f8f9fa; padding: 10px; border-radius: 4px;">' . substr(strip_tags($article->introtext), 0, 300) . '...</div></div>';
}
if ($article->fulltext) {
    echo '<div style="margin-top: 15px;"><div class="info-label">Fulltext</div><div style="background: #f8f9fa; padding: 10px; border-radius: 4px;">' . substr(strip_tags($article->fulltext), 0, 300) . '...</div></div>';
}
echo '</div></div>';

// 2. Assets tabel
echo '<div class="section">';
echo '<div class="section-header">2. PERMISSIONS: ' . htmlspecialchars($prefix) . 'assets</div>';
echo '<div class="section-content">';

$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName($prefix . 'assets'))
    ->where($db->quoteName('id') . ' = ' . (int)$assetId);

$db->setQuery($query);
$asset = $db->loadObject();

if ($asset) {
    echo '<div class="success">✓ Asset record gevonden!</div>';
    echo '<div class="info-grid" style="margin-top: 20px;">';
    echo infoItem('Asset ID', $asset->id);
    echo infoItem('Parent ID', $asset->parent_id);
    echo infoItem('Level', $asset->level);
    echo infoItem('Name', htmlspecialchars($asset->name));
    echo infoItem('Title', htmlspecialchars($asset->title));
    echo '</div>';
    if ($asset->rules && $asset->rules != '{}') {
        echo '<div style="margin-top: 15px;"><div class="info-label">Rules (JSON)</div><div class="json-preview">' . htmlspecialchars(substr($asset->rules, 0, 500)) . '</div></div>';
    }
} else {
    echo '<div class="warning">⚠ Geen asset record gevonden voor asset_id: ' . $assetId . '</div>';
}
echo '</div></div>';

// 3. Content Types
echo '<div class="section">';
echo '<div class="section-header">3. CONTENT TYPE: ' . htmlspecialchars($prefix) . 'content_types</div>';
echo '<div class="section-content">';

$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName($prefix . 'content_types'))
    ->where($db->quoteName('type_alias') . ' = ' . $db->quote('com_content.article'));

$db->setQuery($query);
$contentType = $db->loadObject();

if ($contentType) {
    echo '<div class="success">✓ Content type gevonden!</div>';
    echo '<div class="info-grid" style="margin-top: 20px;">';
    echo infoItem('Type ID', $contentType->type_id);
    echo infoItem('Type Alias', htmlspecialchars($contentType->type_alias));
    echo infoItem('Type Title', htmlspecialchars($contentType->type_title));
    echo infoItem('Table', htmlspecialchars($contentType->table));
    echo '</div>';
    if ($contentType->field_mappings) {
        echo '<div style="margin-top: 15px;"><div class="info-label">Field mappings (JSON)</div><div class="json-preview">' . htmlspecialchars(substr($contentType->field_mappings, 0, 500)) . '</div></div>';
    }
} else {
    echo '<div class="warning">⚠ Content type niet gevonden!</div>';
}
echo '</div></div>';

// 4. Tags
echo '<div class="section">';
echo '<div class="section-header">4. TAGS: ' . htmlspecialchars($prefix) . 'contentitem_tag_map</div>';
echo '<div class="section-content">';

$query = $db->getQuery(true)
    ->select('m.*, t.title as tag_title, t.alias as tag_alias')
    ->from($db->quoteName($prefix . 'contentitem_tag_map', 'm'))
    ->join('LEFT', $db->quoteName($prefix . 'tags', 't'), $db->quoteName('t.id') . ' = ' . $db->quoteName('m.tag_id'))
    ->where($db->quoteName('m.content_item_id') . ' = ' . (int)$articleId)
    ->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_content.article'));

$db->setQuery($query);
$tags = $db->loadObjectList();

if ($tags) {
    echo '<div class="success">✓ ' . count($tags) . ' tag(s) gevonden</div>';
    echo '<table style="margin-top: 15px;"><tr><th>Tag ID</th><th>Titel</th><th>Alias</th></tr>';
    foreach ($tags as $tag) {
        echo '<tr><td>' . $tag->tag_id . '</td><td>' . htmlspecialchars($tag->tag_title) . '</td><td>' . htmlspecialchars($tag->tag_alias) . '</td></tr>';
    }
    echo '</table>';
} else {
    echo '<div class="empty">Geen tags toegewezen</div>';
}
echo '</div></div>';

// 5. Featured
echo '<div class="section">';
echo '<div class="section-header">5. FEATURED: ' . htmlspecialchars($prefix) . 'content_frontpage</div>';
echo '<div class="section-content">';

$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName($prefix . 'content_frontpage'))
    ->where($db->quoteName('content_id') . ' = ' . (int)$articleId);

$db->setQuery($query);
$featured = $db->loadObject();

if ($featured) {
    echo '<div class="success">✓ Artikel is featured!</div>';
    echo '<div class="info-grid" style="margin-top: 20px;">';
    echo infoItem('Content ID', $featured->content_id);
    echo infoItem('Ordering', $featured->ordering);
    echo '</div>';
} else {
    echo '<div class="empty">Artikel is niet featured</div>';
}
echo '</div></div>';

// 6. UCM Content
echo '<div class="section">';
echo '<div class="section-header">6. UCM CONTENT: ' . htmlspecialchars($prefix) . 'ucm_content</div>';
echo '<div class="section-content">';

$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName($prefix . 'ucm_content'))
    ->where($db->quoteName('core_content_item_id') . ' = ' . (int)$articleId)
    ->where($db->quoteName('core_type_alias') . ' = ' . $db->quote('com_content.article'));

$db->setQuery($query);
$ucmContent = $db->loadObject();

if ($ucmContent) {
    echo '<div class="success">✓ UCM content record gevonden!</div>';
    echo '<div class="info-grid" style="margin-top: 20px;">';
    echo infoItem('Core Content ID', $ucmContent->core_content_id);
    echo infoItem('Core Content Item ID', $ucmContent->core_content_item_id);
    echo infoItem('Core Type Alias', htmlspecialchars($ucmContent->core_type_alias));
    echo infoItem('Core Type ID', $ucmContent->core_type_id);
    echo infoItem('Core State', getStatusBadge($ucmContent->core_state));
    echo infoItem('Core Access', $ucmContent->core_access);
    echo infoItem('Core Created User ID', $ucmContent->core_created_user_id);
    echo infoItem('Core Created Time', $ucmContent->core_created_time);
    echo infoItem('Core Modified User ID', $ucmContent->core_modified_user_id ?: '<span class="empty">Niet gewijzigd</span>');
    echo infoItem('Core Modified Time', $ucmContent->core_modified_time ?: '<span class="empty">Niet gewijzigd</span>');
    echo infoItem('Core Language', $ucmContent->core_language);
    echo infoItem('Core Title', htmlspecialchars($ucmContent->core_title));
    echo infoItem('Core Alias', htmlspecialchars($ucmContent->core_alias));
    echo '</div>';
    if ($ucmContent->core_body) {
        echo '<div style="margin-top: 15px;"><div class="info-label">Core Body</div><div class="json-preview">' . htmlspecialchars(substr($ucmContent->core_body, 0, 500)) . '</div></div>';
    }
    if ($ucmContent->core_metadata && $ucmContent->core_metadata != '{}') {
        echo '<div style="margin-top: 15px;"><div class="info-label">Core Metadata (JSON)</div><div class="json-preview">' . htmlspecialchars(substr($ucmContent->core_metadata, 0, 500)) . '</div></div>';
    }
} else {
    echo '<div class="warning">⚠ Geen UCM content record gevonden</div>';
}
echo '</div></div>';

// 7. UCM Base
echo '<div class="section">';
echo '<div class="section-header">7. UCM BASE: ' . htmlspecialchars($prefix) . 'ucm_base</div>';
echo '<div class="section-content">';

if ($ucmContent) {
    $query = $db->getQuery(true)
        ->select('*')
        ->from($db->quoteName($prefix . 'ucm_base'))
        ->where($db->quoteName('ucm_id') . ' = ' . (int)$ucmContent->core_content_id);

    $db->setQuery($query);
    $ucmBase = $db->loadObject();

    if ($ucmBase) {
        echo '<div class="success">✓ UCM base record gevonden!</div>';
        echo '<div class="info-grid" style="margin-top: 20px;">';
        echo infoItem('UCM ID', $ucmBase->ucm_id);
        echo infoItem('UCM Item ID', $ucmBase->ucm_item_id);
        echo infoItem('UCM Type ID', $ucmBase->ucm_type_id);
        echo infoItem('UCM Language ID', $ucmBase->ucm_language_id);
        echo '</div>';
    } else {
        echo '<div class="warning">⚠ Geen UCM base record gevonden</div>';
    }
} else {
    echo '<div class="empty">Overslaan (geen UCM content gevonden)</div>';
}
echo '</div></div>';

// 8. Associations
echo '<div class="section">';
echo '<div class="section-header">8. ASSOCIATIONS: ' . htmlspecialchars($prefix) . 'associations</div>';
echo '<div class="section-content">';

$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName($prefix . 'associations'))
    ->where($db->quoteName('id') . ' = ' . (int)$articleId)
    ->where($db->quoteName('context') . ' = ' . $db->quote('com_content.item'));

$db->setQuery($query);
$associations = $db->loadObjectList();

if ($associations) {
    echo '<div class="success">✓ ' . count($associations) . ' associatie(s) gevonden</div>';
    echo '<table style="margin-top: 15px;"><tr><th>ID</th><th>Context</th><th>Key</th></tr>';
    foreach ($associations as $assoc) {
        echo '<tr><td>' . $assoc->id . '</td><td>' . htmlspecialchars($assoc->context) . '</td><td>' . htmlspecialchars($assoc->key) . '</td></tr>';
    }
    echo '</table>';
} else {
    echo '<div class="empty">Geen associaties gevonden</div>';
}
echo '</div></div>';

// 9. Rating
echo '<div class="section">';
echo '<div class="section-header">9. RATING: ' . htmlspecialchars($prefix) . 'content_rating</div>';
echo '<div class="section-content">';

$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName($prefix . 'content_rating'))
    ->where($db->quoteName('content_id') . ' = ' . (int)$articleId);

$db->setQuery($query);
$rating = $db->loadObject();

if ($rating) {
    echo '<div class="success">✓ Rating record gevonden!</div>';
    $avgRating = $rating->rating_count > 0 ? round($rating->rating_sum / $rating->rating_count, 2) : 0;
    echo '<div class="info-grid" style="margin-top: 20px;">';
    echo infoItem('Content ID', $rating->content_id);
    echo infoItem('Rating Sum', $rating->rating_sum);
    echo infoItem('Rating Count', $rating->rating_count);
    echo infoItem('Gemiddelde Rating', $avgRating . ' / 5');
    echo '</div>';
} else {
    echo '<div class="empty">Geen rating record gevonden</div>';
}
echo '</div></div>';

// 10. Content History
echo '<div class="section">';
echo '<div class="section-header">10. CONTENT HISTORY: ' . htmlspecialchars($prefix) . 'content_history</div>';
echo '<div class="section-content">';

$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName($prefix . 'content_history'))
    ->where($db->quoteName('ucm_item_id') . ' = ' . (int)$articleId)
    ->where($db->quoteName('ucm_type_id') . ' = ' . (int)($contentType->type_id ?? 0))
    ->order($db->quoteName('save_date') . ' DESC')
    ->setLimit(5);

$db->setQuery($query);
$history = $db->loadObjectList();

if ($history) {
    echo '<div class="success">✓ ' . count($history) . ' versie(s) gevonden</div>';
    echo '<table style="margin-top: 15px;"><tr><th>Versie</th><th>Opgeslagen</th><th>Door User ID</th></tr>';
    foreach ($history as $version) {
        echo '<tr><td>' . htmlspecialchars($version->version_note ?: 'Geen notitie') . '</td><td>' . $version->save_date . '</td><td>' . $version->editor_user_id . '</td></tr>';
    }
    echo '</table>';
} else {
    echo '<div class="empty">Geen versiegeschiedenis gevonden</div>';
}
echo '</div></div>';

// 11. Categorie
echo '<div class="section">';
echo '<div class="section-header">11. CATEGORIE: ' . htmlspecialchars($prefix) . 'categories</div>';
echo '<div class="section-content">';

$query = $db->getQuery(true)
    ->select('*')
    ->from($db->quoteName($prefix . 'categories'))
    ->where($db->quoteName('id') . ' = ' . (int)$article->catid);

$db->setQuery($query);
$category = $db->loadObject();

if ($category) {
    echo '<div class="success">✓ Categorie gevonden!</div>';
    echo '<div class="info-grid" style="margin-top: 20px;">';
    echo infoItem('Categorie ID', $category->id);
    echo infoItem('Titel', htmlspecialchars($category->title));
    echo infoItem('Alias', htmlspecialchars($category->alias));
    echo infoItem('Asset ID', $category->asset_id);
    echo infoItem('Parent ID', $category->parent_id);
    echo infoItem('Level', $category->level);
    echo infoItem('Path', htmlspecialchars($category->path));
    echo infoItem('Taal', $category->language);
    echo '</div>';
} else {
    echo '<div class="warning">⚠ Categorie niet gevonden voor catid: ' . $article->catid . '</div>';
}
echo '</div></div>';

// 12. Auteur
echo '<div class="section">';
echo '<div class="section-header">12. AUTEUR: ' . htmlspecialchars($prefix) . 'users</div>';
echo '<div class="section-content">';

if ($article->created_by) {
    $query = $db->getQuery(true)
        ->select('id, name, username, email')
        ->from($db->quoteName($prefix . 'users'))
        ->where($db->quoteName('id') . ' = ' . (int)$article->created_by);

    $db->setQuery($query);
    $author = $db->loadObject();

    if ($author) {
        echo '<div class="success">✓ Auteur gevonden!</div>';
        echo '<div class="info-grid" style="margin-top: 20px;">';
        echo infoItem('User ID', $author->id);
        echo infoItem('Naam', htmlspecialchars($author->name));
        echo infoItem('Username', htmlspecialchars($author->username));
        echo infoItem('Email', htmlspecialchars($author->email));
        echo '</div>';
    } else {
        echo '<div class="warning">⚠ Auteur niet gevonden voor user_id: ' . $article->created_by . '</div>';
    }
} else {
    echo '<div class="empty">Geen auteur opgegeven</div>';
}
echo '</div></div>';

// 13. Workflow
echo '<div class="section">';
echo '<div class="section-header">13. WORKFLOW: ' . htmlspecialchars($prefix) . 'workflow_stages</div>';
echo '<div class="section-content">';

$query = $db->getQuery(true)
    ->select('COUNT(*)')
    ->from($db->quoteName($prefix . 'workflow_stages'));

$db->setQuery($query);
$hasWorkflow = $db->loadResult() > 0;

if ($hasWorkflow) {
    $query = $db->getQuery(true)
        ->select('s.*')
        ->from($db->quoteName($prefix . 'workflow_stages', 's'))
        ->join('INNER', $db->quoteName($prefix . 'content', 'c'), 
               $db->quoteName('c.workflow_id') . ' = ' . $db->quoteName('s.workflow_id'))
        ->where($db->quoteName('c.id') . ' = ' . (int)$articleId);

    $db->setQuery($query);
    $stage = $db->loadObject();

    if ($stage) {
        echo '<div class="success">✓ Workflow stage gevonden!</div>';
        echo '<div class="info-grid" style="margin-top: 20px;">';
        echo infoItem('Stage ID', $stage->id);
        echo infoItem('Titel', htmlspecialchars($stage->title));
        echo infoItem('Workflow ID', $stage->workflow_id);
        echo '</div>';
    } else {
        echo '<div class="empty">Geen workflow stage gevonden</div>';
    }
} else {
    echo '<div class="empty">Workflow niet geconfigureerd</div>';
}
echo '</div></div>';

// Samenvatting
echo '<div class="summary">';
echo '<h2>📊 Samenvatting</h2>';
echo '<div class="summary-grid">';
echo '<div class="summary-item"><div class="summary-label">Artikel ID</div><div class="summary-value">' . $articleId . '</div></div>';
echo '<div class="summary-item"><div class="summary-label">Asset ID</div><div class="summary-value">' . $assetId . '</div></div>';
echo '<div class="summary-item"><div class="summary-label">Titel</div><div class="summary-value">' . htmlspecialchars($article->title) . '</div></div>';
echo '<div class="summary-item"><div class="summary-label">Status</div><div class="summary-value">' . ($article->state == 1 ? 'Gepubliceerd' : ($article->state == 0 ? 'Ongepubliceerd' : 'In prullenbak')) . '</div></div>';
echo '<div class="summary-item"><div class="summary-label">Tags</div><div class="summary-value">' . count($tags) . '</div></div>';
echo '<div class="summary-item"><div class="summary-label">Featured</div><div class="summary-value">' . ($featured ? 'Ja' : 'Nee') . '</div></div>';
echo '<div class="summary-item"><div class="summary-label">UCM Record</div><div class="summary-value">' . ($ucmContent ? 'Ja' : 'Nee') . '</div></div>';
echo '<div class="summary-item"><div class="summary-label">Associaties</div><div class="summary-value">' . count($associations) . '</div></div>';
echo '<div class="summary-item"><div class="summary-label">Versies</div><div class="summary-value">' . count($history) . '</div></div>';
echo '</div>';
echo '</div>';

?>
        </div>
    </div>
</body>
</html>
