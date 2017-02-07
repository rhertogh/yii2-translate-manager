<?php

namespace lajax\translatemanager\commands;

use lajax\translatemanager\models\Language;
use lajax\translatemanager\Module;
use lajax\translatemanager\services\Generator;
use lajax\translatemanager\services\Optimizer;
use lajax\translatemanager\services\Scanner;
use Yii;
use yii\base\InvalidCallException;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Command for scanning and optimizing project translations
 *
 * @author Tobias Munk <schmunk@usrbin.de>
 * @since 1.2.8
 */
class TranslatemanagerController extends Controller {

    /**
     * @inheritdoc
     */
    public $defaultAction = 'help';

    /**
     * Display this help.
     */
    public function actionHelp() {
        $this->run('/help', [$this->id]);
    }

    /**
     * Detecting new language elements.
     */
    public function actionScan() {
        $this->stdout("Scanning translations...\n", Console::BOLD);
        $scanner = new Scanner();

        $items = $scanner->run();
        $this->stdout("{$items} new item(s) inserted into database.\n");
    }

    /**
     * Removing unused language elements.
     */
    public function actionOptimize() {
        $this->stdout("Optimizing translations...\n", Console::BOLD);
        $optimizer = new Optimizer();
        $items = $optimizer->run();
        $this->stdout("{$items} removed from database.\n");
    }

    /**
     * Generate javascript translations files.
     * @param string $dir The path to the tmpDir, e.g. "runtime"
     * @param string|int $languages Comma separated list of languages to generate, e.g. "en-US,en-GB"
     * or an integer of the language status code. defaults to active languages, use -1 for all languages
     * @param string $moduleName The name of the 'Translatemanager' module (only needs to be set if the default name is not used)
     */
    public function actionGenerate($dir, $languages = Language::STATUS_ACTIVE, $moduleName = 'translatemanager'){
        $this->stdout("Generating javascript translations...\n", Console::BOLD);

        /** @var Module $module */
        $module = Yii::$app->getModule($moduleName);
        if (is_null($module) || !is_a($module, Module::className())){
            throw new InvalidCallException("Module '$moduleName' not found or not a Translatemanager module");
        }

        $module->tmpDir = $dir;

        $languageQuery = Language::find()->select('language_id');

        if (is_numeric($languages)) {
            if ($languages != -1) {
                $languageQuery->andWhere(['status' => $languages]);
            }
        } else {
            $languageQuery->andWhere(['language_id' => explode(',', $languages)]);
        }

        $languageIds = $languageQuery->column();

        if (count($languageIds)) {
            foreach ($languageIds as $languageId) {
                $generator = new Generator($module, $languageId);
                $result = $generator->run();
                $this->stdout("{$languageId} {$result} item(s)\n");
            }
            $this->stdout("Generated javascript translations for " . count($languageIds) . " language(s)\n");
        } else {
            $this->stdout("No languages to generate\n");
        }
    }
}
