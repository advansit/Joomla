<?php
/**
 * @package     J2Commerce Privacy System Plugin
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH
 * @license     Proprietary
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

class Plgprivacyj2commerceInstallerScript extends InstallerScript
{
    protected $minimumJoomla = '4.0';
    protected $minimumPhp = '7.4';

    public function postflight($type, $parent)
    {
        if ($type === 'install' || $type === 'update') {
            $app = Factory::getApplication();
            
            $message = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
            $message .= '<h2 style="color: #059669; margin-bottom: 20px;">✅ Privacy J2Commerce Plugin erfolgreich installiert!</h2>';
            
            // Critical Warning Box
            $message .= '<div style="background: #fee2e2; border: 2px solid #dc2626; border-radius: 8px; padding: 20px; margin: 20px 0;">';
            $message .= '<h3 style="color: #991b1b; margin-top: 0;">🚨 KRITISCH: Konfiguration erforderlich!</h3>';
            $message .= '<p style="font-size: 16px; font-weight: bold; color: #991b1b;">Das Plugin ist NICHT einsatzbereit ohne vollständige Konfiguration!</p>';
            $message .= '<p style="color: #7f1d1d;">Geschätzte Konfigurationszeit: <strong>20-30 Minuten</strong></p>';
            $message .= '</div>';
            
            // Step 1: Enable Plugin
            $message .= '<div style="background: #f0f9ff; border-left: 5px solid #0ea5e9; padding: 20px; margin: 20px 0; border-radius: 4px;">';
            $message .= '<h3 style="color: #0369a1; margin-top: 0;">📌 SCHRITT 1: Plugin aktivieren</h3>';
            $message .= '<p style="font-size: 15px;"><strong>Navigation:</strong> <code style="background: #e0f2fe; padding: 4px 8px; border-radius: 4px;">System → Plugins</code></p>';
            $message .= '<ol style="line-height: 1.8;">';
            $message .= '<li>Suche nach: <strong>"Privacy - J2Commerce"</strong></li>';
            $message .= '<li>Status ändern auf: <strong style="color: #059669;">Enabled</strong></li>';
            $message .= '<li>Speichern</li>';
            $message .= '</ol>';
            $message .= '<p style="background: #fef3c7; padding: 10px; border-radius: 4px; margin-top: 10px;">⚠️ <strong>Ohne Aktivierung:</strong> Plugin ist nicht funktionsfähig!</p>';
            $message .= '</div>';
            
            // Step 2: J2Store Custom Field
            $message .= '<div style="background: #fef3c7; border-left: 5px solid #f59e0b; padding: 20px; margin: 20px 0; border-radius: 4px;">';
            $message .= '<h3 style="color: #92400e; margin-top: 0;">🔧 SCHRITT 2: J2Store Custom Field erstellen (PFLICHT!)</h3>';
            $message .= '<p style="font-size: 15px;"><strong>Navigation:</strong> <code style="background: #fef3c7; padding: 4px 8px; border-radius: 4px;">Components → J2Store → Setup → Custom Fields → New</code></p>';
            
            $message .= '<div style="background: white; padding: 15px; border-radius: 4px; margin: 15px 0;">';
            $message .= '<p style="font-weight: bold; color: #dc2626; margin-bottom: 10px;">⚠️ Diese Einstellungen EXAKT übernehmen:</p>';
            $message .= '<table style="width: 100%; border-collapse: collapse;">';
            $message .= '<tr style="background: #f9fafb;"><td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">Field Name</td><td style="padding: 8px; border: 1px solid #e5e7eb;"><code style="background: #fee2e2; padding: 2px 6px; color: #dc2626; font-weight: bold;">is_lifetime_license</code> (exakt so!)</td></tr>';
            $message .= '<tr><td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">Field Label</td><td style="padding: 8px; border: 1px solid #e5e7eb;">Lifetime License</td></tr>';
            $message .= '<tr style="background: #f9fafb;"><td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">Field Type</td><td style="padding: 8px; border: 1px solid #e5e7eb;">Radio</td></tr>';
            $message .= '<tr><td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">Display in</td><td style="padding: 8px; border: 1px solid #e5e7eb;">Product</td></tr>';
            $message .= '<tr style="background: #f9fafb;"><td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">Required</td><td style="padding: 8px; border: 1px solid #e5e7eb;">No</td></tr>';
            $message .= '<tr><td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">Published</td><td style="padding: 8px; border: 1px solid #e5e7eb;"><strong style="color: #059669;">Yes</strong></td></tr>';
            $message .= '<tr style="background: #f9fafb;"><td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">Options</td><td style="padding: 8px; border: 1px solid #e5e7eb;">Yes / No</td></tr>';
            $message .= '<tr><td style="padding: 8px; border: 1px solid #e5e7eb; font-weight: bold;">Default Value</td><td style="padding: 8px; border: 1px solid #e5e7eb;">No</td></tr>';
            $message .= '</table>';
            $message .= '</div>';
            
            $message .= '<div style="background: #fee2e2; padding: 15px; border-radius: 4px; margin-top: 15px;">';
            $message .= '<p style="font-weight: bold; color: #991b1b; margin: 0;">🚨 KRITISCH: Ohne dieses Custom Field werden Lifetime-Lizenzen NICHT erkannt!</p>';
            $message .= '<p style="color: #7f1d1d; margin: 10px 0 0 0;">Folge: Kundendaten werden nach Retention-Periode gelöscht → Lizenz-Reaktivierung unmöglich!</p>';
            $message .= '</div>';
            $message .= '</div>';
            
            // Step 3: Classify Products
            $message .= '<div style="background: #f0fdf4; border-left: 5px solid #10b981; padding: 20px; margin: 20px 0; border-radius: 4px;">';
            $message .= '<h3 style="color: #065f46; margin-top: 0;">🏷️ SCHRITT 3: Produkte klassifizieren</h3>';
            $message .= '<p style="font-size: 15px;"><strong>Navigation:</strong> <code style="background: #d1fae5; padding: 4px 8px; border-radius: 4px;">Components → J2Store → Catalog → Products</code></p>';
            $message .= '<p><strong>Für JEDES Produkt mit Lifetime-Lizenz:</strong></p>';
            $message .= '<ol style="line-height: 1.8;">';
            $message .= '<li>Produkt öffnen</li>';
            $message .= '<li>Zum Abschnitt <strong>"Custom Fields"</strong> scrollen</li>';
            $message .= '<li>Feld <strong>"Lifetime License"</strong> auf <strong style="color: #059669;">Yes</strong> setzen</li>';
            $message .= '<li>Speichern</li>';
            $message .= '</ol>';
            $message .= '<p style="background: #dbeafe; padding: 10px; border-radius: 4px; margin-top: 10px;">💡 <strong>Tipp:</strong> Für reguläre Produkte auf "No" belassen (Standard)</p>';
            $message .= '</div>';
            
            // Step 4: Configure Plugin
            $message .= '<div style="background: #fef3c7; border-left: 5px solid #f59e0b; padding: 20px; margin: 20px 0; border-radius: 4px;">';
            $message .= '<h3 style="color: #92400e; margin-top: 0;">⚙️ SCHRITT 4: Plugin-Einstellungen konfigurieren</h3>';
            $message .= '<p style="font-size: 15px;"><strong>Navigation:</strong> <code style="background: #fef3c7; padding: 4px 8px; border-radius: 4px;">System → Plugins → Privacy - J2Commerce</code></p>';
            
            $message .= '<div style="background: white; padding: 15px; border-radius: 4px; margin: 15px 0;">';
            $message .= '<h4 style="margin-top: 0; color: #92400e;">Pflichtfelder:</h4>';
            $message .= '<table style="width: 100%; border-collapse: collapse;">';
            $message .= '<tr style="background: #f9fafb;"><td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold; width: 40%;">Retention Period (Years)</td><td style="padding: 10px; border: 1px solid #e5e7eb;"><strong>10</strong> (Schweiz/Deutschland)<br><small>Österreich: 7, UK: 6, Spanien: 6</small></td></tr>';
            $message .= '<tr><td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Legal Basis</td><td style="padding: 10px; border: 1px solid #e5e7eb;"><code>• Switzerland: OR Art. 958f (10 years)</code><br><small>Ihre rechtliche Grundlage eintragen!</small></td></tr>';
            $message .= '<tr style="background: #fee2e2;"><td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: bold;">Support Email</td><td style="padding: 10px; border: 1px solid #e5e7eb;"><strong style="color: #dc2626;">MUSS geändert werden!</strong><br><code>privacy@ihre-firma.ch</code><br><small>Diese Email wird Benutzern angezeigt!</small></td></tr>';
            $message .= '</table>';
            $message .= '</div>';
            
            $message .= '<div style="background: white; padding: 15px; border-radius: 4px; margin: 15px 0;">';
            $message .= '<h4 style="margin-top: 0; color: #92400e;">Empfohlene Einstellungen:</h4>';
            $message .= '<ul style="line-height: 1.8;">';
            $message .= '<li><strong>Include Joomla Core Data:</strong> Yes (für vollständige Exports)</li>';
            $message .= '<li><strong>Anonymize Orders:</strong> Yes (empfohlen für Buchhaltung)</li>';
            $message .= '<li><strong>Delete Addresses:</strong> Yes (GDPR-konform)</li>';
            $message .= '</ul>';
            $message .= '</div>';
            $message .= '</div>';
            
            // Step 5: Scheduled Task
            $message .= '<div style="background: #f0f9ff; border-left: 5px solid #0ea5e9; padding: 20px; margin: 20px 0; border-radius: 4px;">';
            $message .= '<h3 style="color: #0369a1; margin-top: 0;">⏰ SCHRITT 5: Automatische Bereinigung einrichten (EMPFOHLEN)</h3>';
            $message .= '<p style="font-size: 15px;"><strong>Navigation:</strong> <code style="background: #e0f2fe; padding: 4px 8px; border-radius: 4px;">System → Scheduled Tasks → New</code></p>';
            $message .= '<ol style="line-height: 1.8;">';
            $message .= '<li>Task-Typ wählen: <strong>"J2Commerce - Automatic Data Cleanup"</strong></li>';
            $message .= '<li>Ausführung: <strong>Daily</strong></li>';
            $message .= '<li>Zeit: <strong>02:00</strong> (nachts, geringe Last)</li>';
            $message .= '<li>Status: <strong style="color: #059669;">Enabled</strong></li>';
            $message .= '<li>Speichern</li>';
            $message .= '</ol>';
            $message .= '<p style="background: #dbeafe; padding: 10px; border-radius: 4px; margin-top: 10px;">💡 <strong>Funktion:</strong> Löscht automatisch abgelaufene Daten nach Retention-Periode</p>';
            $message .= '</div>';
            
            // Verification Checklist
            $message .= '<div style="background: #f9fafb; border: 2px solid #6b7280; border-radius: 8px; padding: 20px; margin: 20px 0;">';
            $message .= '<h3 style="color: #374151; margin-top: 0;">✅ Konfigurations-Checkliste</h3>';
            $message .= '<p>Vor Produktiv-Einsatz prüfen:</p>';
            $message .= '<ul style="line-height: 2;">';
            $message .= '<li>☐ Plugin aktiviert</li>';
            $message .= '<li>☐ J2Store Custom Field <code>is_lifetime_license</code> erstellt</li>';
            $message .= '<li>☐ Lifetime-Lizenz-Produkte klassifiziert</li>';
            $message .= '<li>☐ Retention Period konfiguriert</li>';
            $message .= '<li>☐ Legal Basis dokumentiert</li>';
            $message .= '<li>☐ <strong style="color: #dc2626;">Support Email geändert</strong></li>';
            $message .= '<li>☐ Scheduled Task erstellt und aktiviert</li>';
            $message .= '<li>☐ Test-Export durchgeführt (optional)</li>';
            $message .= '</ul>';
            $message .= '</div>';
            
            // Documentation & Support
            $message .= '<div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 20px 0;">';
            $message .= '<h3 style="color: #374151; margin-top: 0;">📚 Dokumentation & Support</h3>';
            $message .= '<p><strong>📖 Vollständige Dokumentation:</strong> Siehe <code>README.md</code> im Plugin-Ordner</p>';
            $message .= '<p><strong>🔍 Detaillierte Anleitung:</strong> Alle Schritte mit Screenshots und Beispielen</p>';
            $message .= '<p><strong>🌐 Support:</strong> <a href="https://advans.ch" target="_blank" style="color: #0ea5e9; text-decoration: none; font-weight: bold;">advans.ch</a></p>';
            $message .= '<p><strong>📧 Email:</strong> support@advans.ch</p>';
            $message .= '</div>';
            
            // Final Warning
            $message .= '<div style="background: #fee2e2; border: 2px solid #dc2626; border-radius: 8px; padding: 20px; margin: 20px 0;">';
            $message .= '<h3 style="color: #991b1b; margin-top: 0;">⚠️ WICHTIGER HINWEIS</h3>';
            $message .= '<p style="font-size: 15px; font-weight: bold; color: #991b1b;">Das Plugin ist NICHT GDPR-konform ohne vollständige Konfiguration!</p>';
            $message .= '<p style="color: #7f1d1d;">Bitte alle 5 Schritte durchführen, bevor Sie das Plugin produktiv einsetzen.</p>';
            $message .= '<p style="color: #7f1d1d; margin-bottom: 0;"><strong>Geschätzte Zeit:</strong> 20-30 Minuten für vollständige Einrichtung</p>';
            $message .= '</div>';
            
            $message .= '</div>';
            
            $app->enqueueMessage($message, 'message');
        }
    }
}
