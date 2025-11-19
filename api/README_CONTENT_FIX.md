# Diagnose en Reparatie Scripts voor ENGINE_content Artikelen

## Probleem
Artikelen staan in de database tabel `ENGINE_content` maar zijn niet zichtbaar in de Joomla backend.

## Mogelijke Oorzaken
1. **Ontbrekende Asset Entries**: Joomla gebruikt de `#__assets` tabel voor ACL (Access Control List). Zonder asset entries kunnen artikelen niet correct worden weergegeven.
2. **Verkeerde Tabelnaam**: Joomla verwacht meestal `#__content` (met database prefix) in plaats van `ENGINE_content`.
3. **Ontbrekende Content Types**: De `#__content_types` tabel moet een entry hebben voor `com_content.article`.

## Scripts

### 1. check_table_name.php
Controleert of de tabelnaam correct is en vergelijkt de structuur met de standaard Joomla content tabel.

**Gebruik:**
```bash
php check_table_name.php
```

### 2. check_content_relations.php
Diagnosticeert welke relaties ontbreken tussen artikelen en andere tabellen.

**Gebruik:**
```bash
php check_content_relations.php
```

**Controleert:**
- Aantal artikelen in ENGINE_content
- Artikelen zonder asset_id
- Artikelen zonder asset entry in #__assets tabel
- Content types configuratie

### 3. fix_content_assets.php
Repareert ontbrekende asset entries voor artikelen.

**WAARSCHUWING:** Dit script maakt database wijzigingen. Maak eerst een backup!

**Gebruik:**
```bash
php fix_content_assets.php
```

Het script vraagt om bevestiging voordat het wijzigingen maakt.

**Wat doet het:**
- Vindt artikelen zonder asset entry
- Maakt asset entries aan in de #__assets tabel
- Koppelt asset_id aan artikelen
- Rebuild de assets tree

## Stappenplan

1. **Maak een database backup**
   ```bash
   # Via phpMyAdmin of command line
   mysqldump -u gebruikersnaam -p databasenaam > backup.sql
   ```

2. **Voer diagnose uit**
   ```bash
   php check_table_name.php
   php check_content_relations.php
   ```

3. **Repareer assets (als nodig)**
   ```bash
   php fix_content_assets.php
   ```

4. **Controleer resultaat**
   - Log in op Joomla backend
   - Ga naar Content > Articles
   - Controleer of artikelen nu zichtbaar zijn

## Aanvullende Controles

Als artikelen na het uitvoeren van de scripts nog steeds niet zichtbaar zijn, controleer:

1. **Published Status**
   - Artikelen moeten `state = 1` hebben om gepubliceerd te zijn
   - SQL: `SELECT id, title, state FROM ENGINE_content WHERE state != 1;`

2. **Access Levels**
   - Controleer of de access level van artikelen toegankelijk is voor je gebruiker
   - SQL: `SELECT id, title, access FROM ENGINE_content;`

3. **Category Relaties**
   - Controleer of categorieën bestaan en gepubliceerd zijn
   - SQL: `SELECT c.id, c.title, c.catid, cat.title as category_title 
           FROM ENGINE_content c 
           LEFT JOIN #__categories cat ON c.catid = cat.id;`

4. **Filters in Backend**
   - Controleer of er filters actief zijn in de backend die artikelen verbergen
   - Ga naar Content > Articles en controleer de filter opties

## Handmatige Reparatie (als scripts niet werken)

Als de scripts niet werken, kun je handmatig assets aanmaken:

```sql
-- 1. Vind parent asset ID voor com_content
SELECT id FROM #__assets WHERE name = 'com_content';

-- 2. Voor elk artikel zonder asset_id:
INSERT INTO #__assets (parent_id, lft, rgt, level, name, title, rules)
VALUES (
  [parent_id], 
  0, 
  0, 
  0, 
  'com_content.article.[artikel_id]',
  '[artikel_titel]',
  '{}'
);

-- 3. Update artikel met nieuwe asset_id
UPDATE ENGINE_content 
SET asset_id = [nieuwe_asset_id] 
WHERE id = [artikel_id];

-- 4. Rebuild assets tree via Joomla backend:
-- Extensions > Manage > Database > Rebuild Assets
```

## Ondersteuning

Als het probleem blijft bestaan na het uitvoeren van deze scripts, controleer:
- Joomla logs: `/administrator/logs/`
- PHP error logs
- Database logs
- Joomla versie compatibiliteit

