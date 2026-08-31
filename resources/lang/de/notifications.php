<?php

return [
    'audit' => [
        'subject' => '🔒 Sicherheitslücken erkannt - :app',
        'greeting' => 'Sicherheitswarnung für :app',
        'summary' => 'Es wurden **:count Sicherheitslücken** in den Abhängigkeiten Ihrer Anwendung gefunden.',
        'source_summary' => '**:source**: :count Sicherheitslücken gefunden',
        'source_summary_new' => '**:source**: :count neue Sicherheitslücken gefunden',
        'still_open' => ':count bekannte Sicherheitslücken sind weiterhin offen.',
        'more' => '... und :count weitere Sicherheitslücken',
        'action' => 'Vollständigen Bericht ansehen',
        'footer' => 'Bitte prüfen und aktualisieren Sie die betroffenen Pakete so bald wie möglich.',
        'slack_text' => '🔒 Sicherheitslücken in :app erkannt',
        'slack_header' => '🔒 Sicherheitswarnung - :app',
        'slack_summary' => 'Es wurden *:count Sicherheitslücken* in den Abhängigkeiten Ihrer Anwendung gefunden.',
        'slack_source_summary' => '*:source*: :count Sicherheitslücken gefunden',
        'slack_source_summary_new' => '*:source*: :count neue Sicherheitslücken gefunden',
    ],

    'outdated' => [
        'subject' => '📦 Bericht über veraltete Pakete - :app',
        'greeting' => 'Update-Bericht für :app',
        'summary' => 'Es wurden **:count veraltete Pakete** in den Abhängigkeiten Ihrer Anwendung gefunden.',
        'source_summary' => '**:source**: :count veraltete Pakete',
        'more' => '... und :count weitere Pakete',
        'action' => 'Vollständigen Bericht ansehen',
        'footer' => 'Erwägen Sie, diese Pakete auf die neueste Version zu aktualisieren.',
        'slack_text' => '📦 Veraltete Pakete in :app gefunden',
        'slack_header' => '📦 Bericht über veraltete Pakete - :app',
        'slack_summary' => 'Es wurden *:count veraltete Pakete* in den Abhängigkeiten Ihrer Anwendung gefunden.',
        'slack_source_summary' => '*:source*: :count veraltete Pakete',
    ],

    'view_dashboard' => 'Zum Dashboard',
];
