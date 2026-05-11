# Block: Zusatz-Plugin-Verzeichnis

Ein Admin-Block für das Moodle-Dashboard, der alle installierten Zusatz-Plugins
(non-core, non-subplugin) übersichtlich auflistet.

## Features

- **Kompakte Zeilen-Ansicht** — Click-to-expand für Details
- **Suchfeld** + **Typ-Filter** (auth, mod, block, local, tool, …)
- **NEU-Anzeige** für Plugins, die in den letzten 7 Tagen installiert wurden
  (Quelle: `upgrade_log.timemodified`)
- **Kompatibilitäts-Indikator** ✓ / ⚠ / ✗ basierend auf
  `versionrequires`, `pluginsupported`, `pluginincompatible`
- **README-Anzeige** (Markdown-gerendert, max. 8 KB)
- **Links zum Moodle-Plugin-Verzeichnis** (HTTP-verifiziert, 7-Tage-Cache)
  und zu **GitHub** (aus README extrahiert)
- Nur für Site-Admins sichtbar

## Installation

1. ZIP über *Website-Administration → Plugins → Plugin installieren* hochladen
2. ODER manuell entpacken nach `blocks/plugindirectory/` und Upgrade durchlaufen

## Lizenz

GPL v3 or later — siehe Header der Quelldateien.
