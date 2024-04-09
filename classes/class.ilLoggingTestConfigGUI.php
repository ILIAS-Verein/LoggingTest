<?php

/**
 * LoggingTest configuration user interface class
 *
 * @author	Fabain Wolf <wolf@ilias.de>
 * @version $Id$
 *
 *  @ilCtrl_IsCalledBy ilLoggingTestConfigGUI: ilObjComponentSettingsGUI
 *
 */
class ilLoggingTestConfigGUI extends ilPluginConfigGUI
{
    private \ilGlobalTemplateInterface $tpl;
    private \ILIAS\DI\LoggingServices $logger;
    private array $level;
    private ?array $components = null;

    public function __construct()
    {
        global $DIC;
        $this->lng  = $DIC->language();
        $this->lng->loadLanguageModule("log");
        $this->lng->loadLanguageModule("ui");

        $this->tpl = $DIC->ui()->mainTemplate();
        $this->logger = $DIC->logger();
        $this->ui_factory = $DIC->ui()->factory();
        $this->ctrl = $DIC->ctrl();
        $this->renderer = $DIC->ui()->renderer();
        $this->request  = $DIC->http()->request();
        $this->level = ilLogLevel::getLevelOptions();
        unset($this->level[ilLogLevel::OFF]);
        $this->component_repo = $DIC["component.repository"];
        $this->toolbar = $DIC->toolbar();
        $this->settings = ilLoggingDBSettings::getInstance();
    }

    /**
     * Handles all commmands, default is "configure"
     */
    public function performCommand(string $cmd): void
    {
        switch($cmd) {
            case "configure":
                $this->$cmd();
                break;

        }
    }

    private function components()
    {
        if($this->components === null) {
            $this->components = [
                "root" => "Root&ensp;(root)",
                "xlgt" => "LoggingTest&emsp;(xlgt)"
            ];
            foreach(ilLogComponentLevels::getInstance()->getLogComponents() as $component) {
                if ($this->component_repo->hasComponentId(
                    $component->getComponentId()
                )) {
                    $name= $this->component_repo->getComponentById(
                        $component->getComponentId()
                    )->getName() . "&emsp;(" . $component->getComponentId() . ")" ;
                } else {
                    $name = "Unknown&emsp;(" . $component->getComponentId() . ")";
                }
                $this->components[$component->getComponentId()] = $name;
            }

        }

        return $this->components;
    }

    private function form($link)
    {
        return $this->ui_factory->input()->container()->form()->standard($link, [
            "level" => $this->ui_factory->input()->field()->select($this->lng->txt("log_log_level"), $this->level)->withRequired(true)->withValue($this->settings->getLevel()),
            "comp" => $this->ui_factory->input()->field()->select($this->lng->txt("log_components"), $this->components())->withRequired(true)->withValue("root"),
            "text" => $this->ui_factory->input()->field()->textarea($this->lng->txt("message"))->withValue("This is a test...")
        ])->withSubmitLabel("Launch");
    }

    public function info()
    {
        $settings_link = $this->ctrl->getLinkTargetByClass(["iladministrationgui", "ilobjloggingsettingsgui"], "view");
        return $this->ui_factory->item()->standard($this->ui_factory->button()->shy($this->lng->txt("settings"), $settings_link))->withProperties(
            [$this->lng->txt("log_log_level") => $this->level[$this->settings->getLevel()],
             $this->lng->txt("log_cache_level") => $this->settings->isCacheEnabled() ? $this->level[$this->settings->getCacheLevel()] : $this->lng->txt("disabled"),
             $this->lng->txt("log_browser") => $this->settings->isBrowserLogEnabled() ? implode(", ", $this->settings->getBrowserLogUsers()) : $this->lng->txt("disabled"),
             $this->lng->txt("file") => $this->settings->getLogDir() . "/" . $this->settings->getLogFile()
            ]
        );
    }

    /**
     * Configure screen
     */
    public function configure()
    {
        $params = $this->request->getQueryParams();
        $logger = $this->logger->xlgt();
        $message = "This is a test...";
        $level = $params["level"] ?? null;

        $this->toolbar->addText("Fast Launch: ");
        foreach ($this->level as $id => $title) {
            $this->ctrl->setParameter($this, "level", $id);
            $link = $this->ctrl->getLinkTarget($this, "configure");
            $this->toolbar->addComponent($this->ui_factory->button()->standard($title, $link));
        }
        $this->ctrl->setParameter($this, "level", null);
        $this->toolbar->addSeparator();
        $this->toolbar->addComponent($this->ui_factory->button()->shy("Reset", $this->ctrl->getLinkTarget($this, "configure")));
        $form = $this->form($this->ctrl->getLinkTarget($this, "configure"));

        if($this->request->getMethod() === "POST") {
            $form  = $form->withRequest($this->request);
            $data = $form->getData();
            $comp = $data["comp"] ?? null;
            if($data !== null) {
                $level = (int)$data["level"];

                $logger = $this->logger->$comp();
                if($data["text"]  !== "") {
                    $message = $data["text"];
                }
            } else {
                $level = null;
            }
        }

        if($level !== null) {
            switch($level) {
                case ilLogLevel::INFO:$logger->info($message);
                    break;
                case ilLogLevel::NOTICE:$logger->notice($message);
                    break;
                case ilLogLevel::WARNING:$logger->warning($message);
                    break;
                case ilLogLevel::ERROR:$logger->error($message);
                    break;
                case ilLogLevel::CRITICAL:$logger->critical($message);
                    break;
                case ilLogLevel::ALERT:$logger->alert($message);
                    break;
                case ilLogLevel::EMERGENCY:$logger->emergency($message);
                    break;
                case ilLogLevel::DEBUG:default:$logger->debug($message);
                    break;
            }
        }
        $this->tpl->setContent($this->renderer->render([$this->info(), $form]));
    }

}
