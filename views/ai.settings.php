<?php declare(strict_types = 0);

use Modules\AnalistProblem\Lib\AIAnalysisManager;
use Modules\AnalistProblem\Include\CAnalistProblemConfig;

$this->addJsFile('multiselect.js');

$config = CAnalistProblemConfig::getConfig();
$aiManager = new AIAnalysisManager($config);
$providerInfo = $aiManager->getProviderConfig();

// Form for AI settings
$form = (new CForm())
    ->setName('ai-settings-form')
    ->addItem((new CVar('action', 'ai.settings.update'))->removeId());

// AI Enable/Disable
$form->addItem((new CFormList())
    ->addRow(_('Enable AI Analytics'),
        (new CCheckBox('ai_enabled'))
            ->setChecked($config['ai_enabled'])
            ->setUncheckedValue('0')
            ->setLabel(_('Enable AI-powered problem analysis'))
    )
    ->addRow(_('Analysis Cache Duration'),
        (new CNumericBox('cache_duration', $config['cache_duration'], 6))
            ->setWidth(ZBX_TEXTAREA_TINY_WIDTH)
            ->setAttribute('placeholder', '3600')
    )
);

// Provider Selection
$form->addItem((new CFormList())
    ->addRow(_('Provider Preference Order'),
        (new CMultiSelect([
            'name' => 'provider_preference[]',
            'object_name' => 'ai_providers',
            'data' => array_map(function($provider) {
                return ['id' => $provider, 'name' => $provider];
            }, ['ollama', 'openai', 'claude', 'gemini']),
            'selected' => $config['provider_preference'] ?? [],
            'multiple' => true,
            'add_new' => false,
            'styles' => ['width' => '300px']
        ]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
    )
);

// OpenAI Configuration
$form->addItem((new CFormList())
    ->addRow(_('OpenAI API Key'),
        (new CTextBox('openai_api_key', $config['openai_api_key'] ?? ''))
            ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
            ->setAttribute('type', 'password')
            ->setAttribute('placeholder', 'sk-...')
    )
    ->addRow(_('OpenAI Model'),
        (new CComboBox('openai_model', $config['openai_model'] ?? 'gpt-3.5-turbo', null, [
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo' => 'GPT-4 Turbo'
        ]))->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
    )
    ->addRow(_('OpenAI Base URL'),
        (new CTextBox('openai_base_url', $config['openai_base_url'] ?? 'https://api.openai.com/v1'))
            ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
    )
);

// Ollama Configuration
$form->addItem((new CFormList())
    ->addRow(_('Enable Ollama (Local AI)'),
        (new CCheckBox('ollama_enabled'))
            ->setChecked($config['ollama_enabled'] ?? true)
    )
    ->addRow(_('Ollama Base URL'),
        (new CTextBox('ollama_base_url', $config['ollama_base_url'] ?? 'http://localhost:11434'))
            ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
    )
    ->addRow(_('Ollama Model'),
        (new CTextBox('ollama_model', $config['ollama_model'] ?? 'llama2'))
            ->setWidth(ZBX_TEXTAREA_SMALL_WIDTH)
    )
);

// Test Connection Button
$form->addItem(
    (new CButton('test_connection', _('Test AI Connections')))
        ->addClass(ZBX_STYLE_BTN_ALT)
        ->onClick('testAIConnections();')
);

// Provider Status Table
$status_table = new CTableInfo();
$status_table->setHeader([_('Provider'), _('Status'), _('Cost per analysis'), _('Response Time')]);

foreach ($providerInfo['available_providers'] as $provider) {
    $status_icon = $provider['available'] 
        ? (new CSpan())->addClass(ZBX_STYLE_STATUS_GREEN)
        : (new CSpan())->addClass(ZBX_STYLE_STATUS_RED);
    
    $cost = $provider['cost'] > 0 
        ? sprintf('$%.4f', $provider['cost']) 
        : _('Free');
    
    $status_table->addRow([
        $provider['name'],
        $status_icon,
        $cost,
        _('N/A')
    ]);
}

$form->addItem(
    (new CDiv([
        new CTag('h3', false, _('AI Provider Status')),
        $status_table
    ]))->addClass('ai-status-container')
);

// Submit button
$form->addItem(
    (new CSubmitButton(_('Save')))->addClass(ZBX_STYLE_BTN_ALT)
);

// JavaScript for testing connections
$this->addJs(<<<JS
    function testAIConnections() {
        var url = new Curl('zabbix.php');
        url.setArgument('action', 'problemanalist.ai.test');
        
        overlayDialogue({
            'title': _('Testing AI Connections'),
            'content': jQuery('<span>').text(_('Testing connections to AI providers...')),
            'buttons': [
                {
                    'title': _('Close'),
                    'cancel': true,
                    'class': 'btn-alt',
                    'action': function() {}
                }
            ]
        });
        
        jQuery.ajax({
            url: url.getUrl(),
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    overlayDialogue({
                        'title': _('Connection Test Results'),
                        'content': jQuery('<pre>').text(JSON.stringify(response.results, null, 2)),
                        'buttons': [
                            {
                                'title': _('Close'),
                                'cancel': true,
                                'class': 'btn-alt',
                                'action': function() {}
                            }
                        ]
                    });
                } else {
                    alert(response.error);
                }
            }
        });
    }
JS);

$output = [
    'header' => _('AI Analytics Settings'),
    'body' => $form->toString(),
    'buttons' => null
];

echo json_encode($output);
