<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
require __DIR__ . '/vendor/autoload.php';

class Jpz_Sentry extends \Module
{
    const CONFIG_KEY = 'JPZ_SENTRY_CODE';
    public function __construct()
    {
        $this->name = 'jpz_sentry';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Jacopo Zane';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('JPZ Sentry Module', [], 'Modules.Jpz_sentry.Admin');
        $this->description = $this->trans('Add basic sentry code', [], 'Modules.Jpz_sentry.Admin');
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionDispatcherBefore');
    }

    public function hookActionDispatcherBefore($params)
    {
        $sentryEndpoint = \Configuration::get(self::CONFIG_KEY);
        if ($sentryEndpoint) {
            \Sentry\init([
                'dsn' => $sentryEndpoint,
            ]);
        }
    }

    public function getContent(): string
    {
        $output = '';
        if (Tools::isSubmit('submitJpzSentryModule')) {
            $output .= $this->postProcess();
        }

        return $output . $this->renderForm();
    }

    private function renderForm(): string
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitJpzSentryModule';
        $helper->currentIndex = $this->context->link?->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => [
                'sentry_code' => Configuration::get(self::CONFIG_KEY),
            ],
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }
    protected function getConfigForm(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Modules.Jpz_sentry.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Sentry DSN', [], 'Modules.Jpz_sentry.Admin'),
                        'name' => 'sentry_code',
                        'desc' => $this->trans('Enter your Sentry DSN here', [], 'Modules.Jpz_sentry.Admin'),
                        'size' => 50,
                        'required' => true
                    ]
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                    'class' => 'btn btn-default pull-right'
                ],
            ],
        ];
    }

    protected function postProcess(): string
    {
        $sentryCode = Tools::getValue('sentry_code');
        if ($sentryCode) {
            Configuration::updateValue(self::CONFIG_KEY, $sentryCode);

            return $this->displayConfirmation($this->trans('Impostazioni aggiornate con successo.', [], 'Admin.Notifications.Success'));
        } else {
            return $this->displayWarning($this->trans('Nessun codice inserito', [], 'Modules.Jpz_sentry.Admin'));
        }
    }
}
