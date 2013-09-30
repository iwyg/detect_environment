<?php

/**
 * This File is part of the extensions\environment package
 *
 * (c) Thomas Appel <mail@thomas-appel.com>
 *
 * For full copyright and license information, please refer to the LICENSE file
 * that was distributed with this package.
 */

/**
 * Class: extensions.driver
 *
 *
 * @package
 * @version
 * @author Thomas Appel <mail@thomas-appel.com>
 * @license MIT
 */
class Extension_Detect_Environment extends Extension
{
    /**
     * install
     *
     * @access public
     * @return void
     */
    public function install()
    {
        $this->initConfig();
        Symphony::Configuration()->write();
    }

    /**
     * uninstall
     *
     * @access public
     * @return void
     */
    public function uninstall()
    {
        Symphony::Configuration()->remove('environment');
        Symphony::Configuration()->write();
    }

    private function initConfig()
    {
        if (!$env = Symphony::Configuration()->get('environment')) {
            Symphony::Configuration()->setArray(array('environment' => array()));
        }
    }

    /**
     * getSubscribedDelegates
     *
     * @access public
     * @return array
     */
    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page'     => '/frontend/',
                'delegate' => 'FrontendParamsResolve',
                'callback' => 'registerEnv'
            ),
            array(
                'page'     => '/system/preferences/',
                'delegate' => 'AddCustomPreferenceFieldsets',
                'callback' => 'addConfigView'
            ),
            array(
                'page'     => '/system/preferences/',
                'delegate' => 'Save',
                'callback' => 'saveConfig'
            )
        );
    }

    /**
     * registerEnv
     *
     * @param array $context
     * @access public
     * @return void
     */
    public function registerEnv(array $context)
    {
        $context['params']['environment'] = $this->detectEnvironment($this->getConfig());
    }

    /**
     * detectEnvironment
     *
     * @param array $settings
     *
     * @access private
     * @return mixed
     */
    private function detectEnvironment(array $settings)
    {
        extract(parse_url(URL));

        if ($env = array_search($host, $settings)) {
           return strtolower($env);
        }
        return '_default_';
    }

    /**
     * addConfigView
     *
     * @param array $context
     * @access public
     * @return void
     */
    public function addConfigView($context)
    {
        extract($context);

        $env = $this->getConfig();

        if (empty($env)) {
            $this->initConfig();
        }

        $fieldset = new XmlElement('fieldset', null, array('class' => 'settings'));
        $legend   = new XmlElement('legend', __('Environment'), array('class' => 'settings'));
        $fieldset->appendChild($legend);


        $content  = new XmlElement('div', null, array('class' => 'frame'));
        $ol       = new XmlElement('ol', null, array('class' => 'env-duplicator'));

        foreach ($env as $key => $value) {
            $template = $this->createDuplicatorTemplate('instance', $key, $value);
            $ol->appendChild($template);
        }
        $template = $this->createDuplicatorTemplate();
        $ol->appendChild($template);
        $content->appendChild($ol);

        $js = <<<EOF
(function ($, undefined) {
    $(function () {
    $('.env-duplicator').symphonyDuplicator({
            orderable: false,
            collapsible: true
        });
    });
}(this.jQuery));
EOF;
        $script = new XmlElement('script', $js);
        $fieldset->appendChild($content);
        $fieldset->appendChild($script);
        $wrapper->appendChild($fieldset);
    }

    private function createDuplicatorTemplate($listClass = 'template', $env = null, $domain = null)
    {
        $template = new XmlElement('li', null, array('class' => $listClass));
        $header   = new XmlElement('header', 'Environment item', array('data-Name' => 'Environment'));
        $template->appendChild($header);

        $div   = new XmlElement('div', null, array('class' => 'two columns'));
        $input = Widget::input('settings[environment][env][]', $env);
        $label = Widget::label(__('Environment name'), $input, 'column');
        $div->appendChild($label);

        $input = Widget::input('settings[environment][domain][]', $domain);
        $label = Widget::label(__('Domain'), $input, 'column');

        $div->appendChild($label);
        $template->appendChild($div);


        return $template;
    }

    /**
     * saveConfig
     *
     * @param array $settings
     * @param array $errors
     * @access public
     * @return void
     */
    public function saveConfig(array $settings, array $errors = null)
    {
        $data = $settings['settings'];

        if (!isset($data['environment'])) {
            Symphony::Configuration()->remove('environment');
            return;
        }

        extract($data['environment']);
        $settings['settings']['environment'] = array_combine($env, $domain);
    }

    /**
     * getConfig
     *
     * @access protected
     * @return string
     */
    protected function getConfig()
    {
        return is_null($env = Symphony::Configuration()->get('environment')) ? array() : $env;
    }
}
